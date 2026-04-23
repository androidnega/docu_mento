<?php

namespace App\Services;

use App\Models\SmsLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Coordinator-initiated SMS with fresh login URL, username, and password.
 * Sends SMS before persisting the new password so a failed send does not lock the account.
 */
final class SupervisorCredentialSmsService
{
    /**
     * @return array{success: bool, message: string}
     */
    public static function sendLoginSms(User $coordinator, User $supervisor): array
    {
        if (! $coordinator->isDocuMentorCoordinator()) {
            return ['success' => false, 'message' => 'Only coordinators can send supervisor login SMS.'];
        }
        if (! $supervisor->isDocuMentorSupervisor()) {
            return ['success' => false, 'message' => 'Invalid supervisor account.'];
        }
        if (! ArkeselService::hasApiKey()) {
            return ['success' => false, 'message' => 'SMS is not configured. Add an Arkesel API key under Settings → OTP.'];
        }

        $phone = trim((string) ($supervisor->phone ?? ''));
        if ($phone === '') {
            return ['success' => false, 'message' => 'This supervisor has no phone number. Add a phone first.'];
        }

        $coordinator->refresh();
        if ($coordinator->sms_remaining < 1) {
            return ['success' => false, 'message' => 'You have no SMS credits left. Ask an administrator to add SMS allocation.'];
        }

        $plain = Str::password(12);
        $loginUrl = url('/login');
        $message = sprintf(
            'Docu Mento login. URL: %s Username: %s Password: %s',
            $loginUrl,
            $supervisor->username,
            $plain
        );

        $result = ArkeselService::sendSms($phone, $message);
        $ok = (bool) ($result['success'] ?? false);
        SmsLog::logSend($phone, $message, $ok, $result['message'] ?? null, $coordinator->id);

        if (! $ok) {
            return ['success' => false, 'message' => $result['message'] ?? 'SMS could not be sent. Password was not changed.'];
        }

        $supervisor->password = Hash::make($plain);
        $supervisor->remember_token = null;
        $supervisor->save();

        self::invalidateSessions($supervisor);
        $coordinator->increment('sms_used');

        return ['success' => true, 'message' => 'Login details sent by SMS.'];
    }

    private static function invalidateSessions(User $user): void
    {
        if (config('session.driver') === 'database' && Schema::hasColumn(config('session.table', 'sessions'), 'user_id')) {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        }
    }
}
