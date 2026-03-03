<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DepartmentController extends Controller
{
    use InteractsWithAdminSession;

    /** Only Super Admin can create/update/delete departments. */
    private function ensureSuperAdmin(): void
    {
        $user = $this->adminUser();
        if (! $user || ! $user->isSuperAdmin()) {
            abort(403, 'Only Super Administrators can manage departments.');
        }
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureSuperAdmin();

        $rules = ['name' => 'required|string|max:255'];
        if ($request->filled('school_id')) {
            $rules['school_id'] = 'required|exists:schools,id';
        } else {
            $rules['faculty_id'] = 'required|exists:faculties,id';
        }
        $request->validate($rules);

        $department = Department::create([
            'name' => trim($request->name),
            'school_id' => $request->filled('school_id') ? $request->school_id : null,
            'faculty_id' => $request->filled('faculty_id') ? $request->faculty_id : null,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'department' => [
                'id' => $department->id,
                'name' => $department->name,
            ],
        ]);
    }

    public function update(Request $request, Department $department): JsonResponse
    {
        $this->ensureSuperAdmin();

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $department->update([
            'name' => trim($request->name),
        ]);

        return response()->json([
            'success' => true,
            'department' => [
                'id' => $department->id,
                'name' => $department->name,
            ],
        ]);
    }

    public function destroy(Department $department): JsonResponse
    {
        $this->ensureSuperAdmin();

        $department->delete();

        return response()->json([
            'success' => true,
            'message' => 'Department deleted successfully.',
        ]);
    }

    /** Departments for a school (AJAX for user/profile forms). */
    public function bySchool(School $school): JsonResponse
    {
        $departments = $school->departments()->where('is_active', true)->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'departments' => $departments->map(fn($d) => [
                'id' => $d->id,
                'name' => $d->name,
            ]),
        ]);
    }

    /** Legacy: Departments for a faculty (AJAX when using institutions/faculties). */
    public function byFaculty(Faculty $faculty): JsonResponse
    {
        $departments = $faculty->departments()->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'departments' => $departments->map(fn($d) => [
                'id' => $d->id,
                'name' => $d->name,
            ]),
        ]);
    }
}
