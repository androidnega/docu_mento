<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\AdminDashboardController;

class SupervisorController extends Controller
{
    public function index()
    {
        return app(AdminDashboardController::class)->supervisorDashboard();
    }
}

