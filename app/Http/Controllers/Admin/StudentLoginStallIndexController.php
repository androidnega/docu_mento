<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentLoginStallIndex;
use App\Services\StudentLoginStallService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StudentLoginStallIndexController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        if (! (bool) session(SettingsController::SESSION_STALL_SETTINGS_UNLOCKED)) {
            return redirect()->route('dashboard.settings.index')
                ->with('error', 'Unlock the student login stall section with its password first.');
        }

        $request->validate([
            'index_number' => 'required|string|max:100',
            'note' => 'nullable|string|max:255',
        ]);

        $norm = StudentLoginStallService::normalizeIndexNumber($request->input('index_number'));
        if ($norm === '') {
            return redirect()->route('dashboard.settings.index')
                ->with('error', 'Index number is empty after trimming.');
        }

        StudentLoginStallIndex::firstOrCreate(
            ['index_normalized' => $norm],
            ['note' => $request->filled('note') ? trim((string) $request->note) : null]
        );

        return redirect()->route('dashboard.settings.index')
            ->with('success', 'Index added to the login stall list.');
    }

    public function destroy(StudentLoginStallIndex $studentLoginStallIndex): RedirectResponse
    {
        if (! (bool) session(SettingsController::SESSION_STALL_SETTINGS_UNLOCKED)) {
            return redirect()->route('dashboard.settings.index')
                ->with('error', 'Unlock the student login stall section with its password first.');
        }

        $studentLoginStallIndex->delete();

        return redirect()->route('dashboard.settings.index')
            ->with('success', 'Index removed from the login stall list.');
    }
}
