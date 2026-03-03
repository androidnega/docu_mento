<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SchoolController extends Controller
{
    use InteractsWithAdminSession;

    /** List all schools (Super Admin only). */
    public function index(): View
    {
        $schools = School::withCount('departments')->orderBy('name')->get();
        return view('admin.schools.index', compact('schools'));
    }

    public function create(): View
    {
        return view('admin.schools.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:schools,name',
        ]);
        School::create(['name' => trim($request->name), 'is_active' => true]);
        return redirect()->route('dashboard.schools.index')->with('success', 'School created.');
    }

    public function edit(School $school): View
    {
        $school->load('departments');
        return view('admin.schools.edit', compact('school'));
    }

    public function update(Request $request, School $school): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:schools,name,' . $school->id,
            'is_active' => 'boolean',
        ]);
        $school->update([
            'name' => trim($request->name),
            'is_active' => $request->boolean('is_active', true),
        ]);
        return redirect()->route('dashboard.schools.index')->with('success', 'School updated.');
    }

    public function destroy(School $school): RedirectResponse
    {
        if ($school->departments()->exists()) {
            return redirect()->route('dashboard.schools.index')
                ->with('error', 'Cannot delete school that has departments.');
        }
        $school->delete();
        return redirect()->route('dashboard.schools.index')->with('success', 'School deleted.');
    }
}
