<?php

namespace App\Policies\DocuMentor;

use App\Models\DocuMentor\Chapter;
use App\Models\DocuMentor\Project;
use App\Models\DocuMentor\Submission;
use App\Models\User;

class SubmissionPolicy
{
    public function create(User $user, Project $project, Chapter $chapter): bool
    {
        if ($chapter->project_id !== $project->id) {
            return false;
        }
        $staffMaySubmitWhenClosed = $user->isDocuMentorCoordinator()
            || ProjectPolicy::userHasSupervisorStaffContext($user);
        if (! $chapter->is_open && ! $staffMaySubmitWhenClosed) {
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

    public function view(User $user, Submission $submission): bool
    {
        $chapter = $submission->chapter;
        $project = $chapter?->project;
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

    public function update(User $user, Submission $submission): bool
    {
        if ($user->isDocuMentorCoordinator()) {
            return true;
        }
        $project = $submission->chapter?->project;

        return $project && ProjectPolicy::canSupervisorUserAccessProject($user, $project);
    }

    public function delete(User $user, Submission $submission): bool
    {
        if ($user->isDocuMentorCoordinator()) {
            return true;
        }
        $project = $submission->chapter?->project;

        return $project && ProjectPolicy::canSupervisorUserAccessProject($user, $project);
    }
}
