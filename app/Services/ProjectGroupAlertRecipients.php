<?php

namespace App\Services;

use App\Models\DocuMentor\Project;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Picks up to two SMS recipients for project-related student alerts:
 * group leader (if they have a phone) plus one other random member with a phone.
 */
final class ProjectGroupAlertRecipients
{
    /**
     * @return list<string> Normalized digit-only phone numbers (unique), max 2
     */
    public static function studentPhones(Project $project): array
    {
        $group = $project->group;
        if (! $group) {
            return [];
        }

        $leader = $group->leader;
        $members = $group->relationLoaded('members') ? $group->members : $group->members()->get();

        /** @var Collection<int, User> $profileUsers */
        $profileUsers = collect([$leader])->merge($members)->filter()->unique('id')->values();
        if ($profileUsers->isNotEmpty()) {
            User::eagerLoadDocuMentorMemberProfiles($profileUsers);
        }

        $phones = [];

        $push = function (?User $u) use (&$phones): void {
            if (! $u || count($phones) >= 2) {
                return;
            }
            $p = $u->docuMentorSmsPhone();
            if ($p !== null && ! in_array($p, $phones, true)) {
                $phones[] = $p;
            }
        };

        $push($leader);

        $others = $members->filter(fn (User $m) => ! $leader || (int) $m->id !== (int) $leader->id)->shuffle();
        foreach ($others as $m) {
            $push($m);
            if (count($phones) >= 2) {
                break;
            }
        }

        if (count($phones) < 2) {
            foreach ($members->shuffle() as $m) {
                $push($m);
                if (count($phones) >= 2) {
                    break;
                }
            }
        }

        return $phones;
    }
}
