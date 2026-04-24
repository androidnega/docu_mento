<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\StudentLoginStallIndex;
use Illuminate\Support\Facades\Schema;

final class StudentLoginStallService
{
    public static function normalizeIndexNumber(string $index): string
    {
        return strtoupper(trim($index));
    }

    public static function masterEnabled(): bool
    {
        return Setting::getValue(Setting::KEY_STUDENT_LOGIN_STALL_ENABLED, '0') === '1';
    }

    public static function shouldStallForIndex(?string $canonicalIndex): bool
    {
        if (! self::masterEnabled()) {
            return false;
        }
        if ($canonicalIndex === null || trim($canonicalIndex) === '') {
            return false;
        }
        if (! Schema::hasTable('student_login_stall_indices')) {
            return false;
        }

        $norm = self::normalizeIndexNumber($canonicalIndex);

        return StudentLoginStallIndex::where('index_normalized', $norm)->exists();
    }
}
