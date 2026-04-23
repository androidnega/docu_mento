<?php

namespace App\Support;

/**
 * Shared secret for URL-triggered migrations / cache clears.
 * If MIGRATION_RUN_KEY is unset or empty in .env, a deterministic secret is derived from APP_KEY
 * and requests may omit ?key= (one-click). If MIGRATION_RUN_KEY is set, ?key= must match exactly.
 */
final class MigrationRunnerKey
{
    private const DEFAULT_LEGACY = 'DocuMentoMigrate2026Xp9k3m7';

    private const HMAC_SALT = 'docu-mento-migration-runner-v1';

    public static function oneClickModeEnabled(): bool
    {
        return trim((string) (env('MIGRATION_RUN_KEY') ?? '')) === '';
    }

    public static function expectedSecret(): string
    {
        $configured = trim((string) (env('MIGRATION_RUN_KEY') ?? ''));
        if ($configured !== '') {
            return $configured;
        }
        $appKey = (string) config('app.key', '');
        if ($appKey !== '' && $appKey !== 'base64:') {
            return hash_hmac('sha256', $appKey, self::HMAC_SALT);
        }

        return self::DEFAULT_LEGACY;
    }

    public static function validate(?string $provided): bool
    {
        $expected = self::expectedSecret();
        $given = trim((string) ($provided ?? ''));

        if (self::oneClickModeEnabled()) {
            return $given === '' || hash_equals($expected, $given);
        }

        return hash_equals($expected, $given);
    }
}
