<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Schema;

/**
 * SMS copy for supervisors: Documento intro, assigned student count, login URL, credentials, help line.
 */
final class SupervisorLoginSmsBody
{
    /**
     * @param  bool  $forNewAccount  Kept for callers; message format is the same for new/resend flows.
     */
    public static function build(User $supervisor, string $loginUrl, string $username, string $password, bool $forNewAccount = true): string
    {
        unset($forNewAccount);

        $brand = (string) config('documento.brand_line', 'Documento (CS project platform)');
        $help = (string) config('documento.support_phone', '0552477942');
        $n = self::countAssignedStudents($supervisor);

        $intro = "Welcome to {$brand}. You're assigned as a supervisor with {$n} students.";

        return "{$intro}\n\nLogin: {$loginUrl}\nUser: {$username} | Pass: {$password}\nHelp: {$help}";
    }

    private static function countAssignedStudents(User $supervisor): int
    {
        if (! Schema::hasTable('projects') || ! Schema::hasTable('groups')) {
            return 0;
        }

        $ids = collect();
        foreach ($supervisor->supervisedProjects()->with(['group.members', 'group.leader'])->get() as $project) {
            $group = $project->group;
            if (! $group) {
                continue;
            }
            foreach ($group->members ?? [] as $m) {
                $ids->push((int) $m->id);
            }
            if ($group->leader_id) {
                $ids->push((int) $group->leader_id);
            }
        }

        return $ids->unique()->filter()->count();
    }
}
