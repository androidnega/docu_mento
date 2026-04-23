<?php

namespace App\Services;

use App\Models\SmsLog;
use App\Models\User;

/**
 * Sends welcome/login SMS when a coordinator creates a new supervisor account.
 * Bills the coordinator one SMS credit per successful send.
 */
final class NewSupervisorWelcomeSmsService
{
    /**
     * @return array{sent: bool, reason: string}
     */
    public static function trySend(User $coordinator, User $newSupervisor, string $plainPassword): array
    {
        if (! $newSupervisor->isDocuMentorSupervisor()) {
            return ['sent' => false, 'reason' => 'not_supervisor'];
        }
        if (! ArkeselService::hasApiKey()) {
            return ['sent' => false, 'reason' => 'no_arkesel'];
        }

        $phone = trim((string) ($newSupervisor->phone ?? ''));
        if ($phone === '') {
            return ['sent' => false, 'reason' => 'no_phone'];
        }

        $coordinator->refresh();
        if ($coordinator->sms_remaining < 1) {
            return ['sent' => false, 'reason' => 'no_credits'];
        }

        $loginUrl = url('/login');
        $message = sprintf(
            'Docu Mento login. URL: %s Username: %s Password: %s',
            $loginUrl,
            $newSupervisor->username,
            $plainPassword
        );

        $result = ArkeselService::sendSms($phone, $message);
        $ok = (bool) ($result['success'] ?? false);
        SmsLog::logSend($phone, $message, $ok, $result['message'] ?? null, $coordinator->id);

        if (! $ok) {
            return ['sent' => false, 'reason' => 'sms_failed'];
        }

        $coordinator->increment('sms_used');

        return ['sent' => true, 'reason' => ''];
    }
}
