<?php

namespace App\Policies\DocuMentor;

use App\Models\DocuMentor\Chapter;
use App\Models\DocuMentor\Project;
use App\Models\User;

class ChapterPolicy
{
    public function view(User $user, Chapter $chapter, ?Project $project = null): bool
    {
        $project = $project ?? $chapter->project;
        if (!$project) {
            return false;
        }
        if ($user->isDocuMentorCoordinator()) {
            return true;
        }
        if ($user->isDocuMentorStudent()) {
            return $user->docuMentorGroups()->where('groups.id', $project->group_id)->exists();
        }
        if (ProjectPolicy::canSupervisorUserAccessProject($user, $project)) {
            return true;
        }
        return false;
    }

    /**
     * 9. SECURITY: Only supervisor opens/completes chapters (not coordinator). Coordinator can view only.
     */
    public function update(User $user, Chapter $chapter, ?Project $project = null): bool
    {
        $project = $project ?? $chapter->project;
        if (!$project) {
            return false;
        }
        return ProjectPolicy::canSupervisorUserAccessProject($user, $project);
    }

    private function canSupervise(User $user, ?Project $project): bool
    {
        if (!$project) {
            return false;
        }
        return $user->isDocuMentorCoordinator()
            || $user->supervisedProjects()->where('projects.id', $project->id)->exists();
    }
}
