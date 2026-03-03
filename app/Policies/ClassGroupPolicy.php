<?php

namespace App\Policies;

use App\Models\ClassGroup;
use App\Models\User;

class ClassGroupPolicy
{
    /**
     * Admin, Coordinator, and Supervisors can access class groups. Coordinator manages all; Supervisor only their assigned class groups.
     */
    public function viewAny(User $user): bool
    {
        return $user->isStaff() || $user->isDocuMentorCoordinator();
    }

    /**
     * Check if supervisor is assigned to this class group (owns it).
     */
    private function isSupervisorAssignedToClassGroup(User $user, ClassGroup $classGroup): bool
    {
        return (int) $classGroup->supervisor_id === (int) $user->id;
    }

    public function view(User $user, ClassGroup $classGroup): bool
    {
        if ($user->isSuperAdmin() || $user->isDocuMentorCoordinator()) {
            return true;
        }
        if (! $user->isStaff()) {
            return false;
        }
        return $this->isSupervisorAssignedToClassGroup($user, $classGroup);
    }

    /**
     * Admin and Coordinator can create class groups (assign supervisor). Supervisors cannot create.
     */
    public function create(User $user): bool
    {
        if ($user->isSuperAdmin() || $user->isDocuMentorCoordinator()) {
            return true;
        }
        if (!$user->isStaff()) {
            return false;
        }
        return false;
    }

    /**
     * Only Super Admin and Coordinator can update class group or manage students.
     * Supervisor: can view and generate fallback code only.
     */
    public function update(User $user, ClassGroup $classGroup): bool
    {
        if ($user->isSuperAdmin() || $user->isDocuMentorCoordinator()) {
            return true;
        }
        return false;
    }

    /**
     * Only supervisor (assigned to the group) or Super Admin can generate a one-time fallback login code.
     */
    public function generateFallbackCode(User $user, ClassGroup $classGroup): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if (! $user->isStaff() || $user->isDocuMentorCoordinator()) {
            return false;
        }
        return $this->isSupervisorAssignedToClassGroup($user, $classGroup);
    }

    public function delete(User $user, ClassGroup $classGroup): bool
    {
        if ($user->isSuperAdmin() || $user->isDocuMentorCoordinator()) {
            return true;
        }
        return false;
    }
}
