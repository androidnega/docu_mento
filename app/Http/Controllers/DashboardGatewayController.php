<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\DocuMentor\CoordinatorController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class DashboardGatewayController extends Controller
{
    /**
     * Unified dashboard: single route /dashboard, view by role (student/group_leader → student; supervisor; coordinator; admin).
     */
    public function __invoke(): View|RedirectResponse
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return redirect()->route('login')->with('error', 'Please log in.');
        }

        $roleName = $user->roleName();
        if (in_array($roleName, [User::ROLE_NAME_STUDENT, User::ROLE_NAME_GROUP_LEADER], true)) {
            return app(StudentDashboardController::class)->index();
        }
        if ($roleName === User::ROLE_NAME_SUPERVISOR) {
            return app(AdminDashboardController::class)->supervisorDashboard();
        }
        if ($roleName === User::ROLE_NAME_COORDINATOR) {
            return app(CoordinatorController::class)->dashboard();
        }
        if ($roleName === User::ROLE_NAME_ADMIN) {
            return app(AdminDashboardController::class)->index();
        }

        return app(AdminDashboardController::class)->index();
    }
}
