<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class SupervisorController extends Controller
{
    /** No separate supervisor dashboard – use unified /dashboard URL. */
    public function dashboard(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }
}
