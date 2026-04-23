<?php

namespace App\Policies\DocuMentor;

use App\Models\DocuMentor\Project;
use App\Models\Setting;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Supervisors assigned on the project, or supervisors of a child project tagged to this parent.
     */
    public static function supervisorHasProjectAccess(User $user, Project $project): bool
    {
        if ($user->supervisedProjects()->where('projects.id', $project->id)->exists()) {
            return true;
        }

        return Project::where('parent_project_id', $project->id)
            ->whereHas('supervisors', fn ($q) => $q->where('users.id', $user->id))
            ->exists();
    }

    /**
     * True when the user should be treated as Docu Mentor supervisor staff (role, pivot, or child-project supervisor).
     */
    public static function userHasSupervisorStaffContext(User $user): bool
    {
        if ($user->isDocuMentorSupervisor()) {
            return true;
        }
        if ($user->supervisedProjects()->exists()) {
            return true;
        }

        return Project::query()
            ->whereNotNull('parent_project_id')
            ->whereHas('supervisors', fn ($q) => $q->where('users.id', $user->id))
            ->exists();
    }

    /** Supervisor staff who is assigned to this project (directly or via parent/child tagging). */
    public static function canSupervisorUserAccessProject(User $user, Project $project): bool
    {
        return self::userHasSupervisorStaffContext($user)
            && self::supervisorHasProjectAccess($user, $project);
    }

    /**
     * Student: own group projects. Supervisor: supervised. Coordinator: all.
     */
    public function viewAny(User $user): bool
    {
        return $user->isDocuMentorStudent()
            || $user->isDocuMentorCoordinator()
            || self::userHasSupervisorStaffContext($user);
    }

    public function view(User $user, Project $project): bool
    {
        if ($user->isDocuMentorCoordinator()) {
            return true;
        }
        if ($user->isDocuMentorStudent()) {
            if ($user->docuMentorGroups()->where('groups.id', $project->group_id)->exists()) {
                return true;
            }
            // If tagged to previous project: leader of a child project can access parent (proposal + Ch6).
            if (Project::where('parent_project_id', $project->id)->whereHas('group', fn ($q) => $q->where('leader_id', $user->id))->exists()) {
                return true;
            }
            return false;
        }
        if (self::canSupervisorUserAccessProject($user, $project)) {
            return true;
        }
        return false;
    }

    public function create(User $user): bool
    {
        return $user->isDocuMentorStudent();
    }

    /**
     * 9. SECURITY: Students cannot edit project after approval. Only coordinator can update project.
     */
    public function update(User $user, Project $project): bool
    {
        return $user->isDocuMentorCoordinator();
    }

    public function delete(User $user, Project $project): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isDocuMentorCoordinator()) {
            return Setting::getValue(Setting::KEY_ALLOW_COORDINATOR_DELETE_PROJECT, '1') === '1';
        }
        return false;
    }

    /** Authorization for creating a submission on this project's chapter. */
    public function createSubmission(User $user, Project $project, \App\Models\DocuMentor\Chapter $chapter): bool
    {
        if ($chapter->project_id !== $project->id) {
            return false;
        }
        $staffMaySubmitWhenClosed = $user->isDocuMentorCoordinator()
            || self::userHasSupervisorStaffContext($user);
        if (! $chapter->is_open && ! $staffMaySubmitWhenClosed) {
            return false;
        }
        if ($user->isDocuMentorCoordinator()) {
            return true;
        }
        if ($user->isDocuMentorStudent()) {
            // Member of project's group (via group_members)
            if ($user->docuMentorGroups()->where('groups.id', $project->group_id)->exists()) {
                return true;
            }
            // Leader of project's group (coordinator may set leader_id without adding to group_members)
            if ($project->group && $project->group->leader_id === $user->id) {
                return true;
            }
            return false;
        }
        if (self::canSupervisorUserAccessProject($user, $project)) {
            return true;
        }
        return false;
    }
}
