<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Student\StudentDashboardController;

class StudentController extends Controller
{
    public function index()
    {
        return app(StudentDashboardController::class)->index();
    }
}

