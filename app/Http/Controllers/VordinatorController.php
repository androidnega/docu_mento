<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\AdminDashboardController;

class VordinatorController extends Controller
{
    public function index()
    {
        return app(AdminDashboardController::class)->adminDashboard();
    }
}

