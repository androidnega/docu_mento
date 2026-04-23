<?php

namespace App\Services;

use App\Models\User;

/**
 * Short SMS copy for supervisors: intro to Docu Mento + assignment hint + credentials.
 */
final class SupervisorLoginSmsBody
{
    public static function build(User $supervisor, string $loginUrl, string $username, string $password, bool $forNewAccount): string
    {
        $intro = self::introLine($supervisor, $forNewAccount);

        return $intro.sprintf('URL: %s Username: %s Password: %s', $loginUrl, $username, $password);
    }

    private static function introLine(User $supervisor, bool $forNewAccount): string
    {
        $n = (int) $supervisor->supervisedProjects()->count();

        if ($n > 0) {
            return $n === 1
                ? 'Docu Mento: 1 project assigned—sign in below. '
                : "Docu Mento: {$n} projects assigned—sign in below. ";
        }

        if ($forNewAccount) {
            return 'Docu Mento: supervisor account—sign in; groups appear when linked. ';
        }

        return 'Docu Mento: supervisor—sign in for your dashboard. ';
    }
}
