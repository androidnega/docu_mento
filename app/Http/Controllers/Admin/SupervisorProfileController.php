<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\Faculty;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class SupervisorProfileController extends Controller
{
    use InteractsWithAdminSession;

    public function updateFacultyDepartment(Request $request): RedirectResponse|JsonResponse
    {
        $user = $this->adminUser();
        if (!$user || !$user->isDocuMentorSupervisor()) {
            abort(403, 'Only supervisors can update their faculty and department.');
        }

        $validator = Validator::make($request->all(), [
            'faculty_id' => 'required|exists:faculties,id',
            'department_id' => 'required|exists:departments,id',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            return redirect()->route('dashboard.profile.show')
                ->withErrors($validator)
                ->withInput();
        }

        // Verify department belongs to faculty
        $department = Department::findOrFail($request->department_id);
        if ((int) $department->faculty_id !== (int) $request->faculty_id) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected department does not belong to the selected faculty.',
                ], 422);
            }

            return redirect()->route('dashboard.profile.show')
                ->withErrors(['department_id' => 'Selected department does not belong to the selected faculty.'])
                ->withInput();
        }

        // Verify faculty belongs to supervisor's institution
        $faculty = Faculty::findOrFail($request->faculty_id);
        if ($user->institution_id && (int) $faculty->institution_id !== (int) $user->institution_id) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected faculty does not belong to your institution.',
                ], 422);
            }

            return redirect()->route('dashboard.profile.show')
                ->withErrors(['faculty_id' => 'Selected faculty does not belong to your institution.'])
                ->withInput();
        }

        $user->faculty_id = $request->faculty_id;
        $user->department_id = $request->department_id;
        $user->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Faculty and department updated successfully.',
            ]);
        }

        return redirect()->route('dashboard.profile.show')
            ->with('success', 'Faculty and department updated successfully.');
    }
}
