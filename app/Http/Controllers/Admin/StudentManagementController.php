<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithAdminSession;
use App\Models\ValidIndex;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StudentManagementController extends Controller
{
    use InteractsWithAdminSession;
    public function index(Request $request): View
    {
        $query = ValidIndex::query()->orderBy('index_number');
        if ($request->filled('index_number')) {
            $query->where('index_number', 'like', '%' . trim($request->index_number) . '%');
        }
        $students = $query->paginate(20)->withQueryString();
        return view('admin.students.index', compact('students'));
    }

    public function create(): View
    {
        return view('admin.students.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'index_number' => 'required|string|max:64',
            'student_name' => 'nullable|string|max:255',
        ]);
        $indexNumber = trim($request->index_number);
        ValidIndex::updateOrCreate(
            ['index_number' => $indexNumber],
            ['student_name' => $request->filled('student_name') ? trim($request->student_name) : null]
        );
        return redirect()->route('admin.students.index')->with('success', 'Student index added.');
    }

    public function edit(ValidIndex $validIndex): View
    {
        return view('admin.students.edit', compact('validIndex'));
    }

    public function update(Request $request, ValidIndex $validIndex): RedirectResponse
    {
        $request->validate([
            'index_number' => 'required|string|max:64',
            'student_name' => 'nullable|string|max:255',
        ]);
        $indexNumber = trim($request->index_number);
        if ($validIndex->index_number !== $indexNumber && ValidIndex::where('index_number', $indexNumber)->where('id', '!=', $validIndex->id)->exists()) {
            return back()->withInput()->withErrors(['index_number' => 'This index number is already in use.']);
        }
        $validIndex->update([
            'index_number' => $indexNumber,
            'student_name' => $request->filled('student_name') ? trim($request->student_name) : null,
        ]);
        return redirect()->route('admin.students.index')->with('success', 'Student updated.');
    }

    public function destroy(ValidIndex $validIndex): RedirectResponse
    {
        $validIndex->delete();
        return redirect()->route('admin.students.index')->with('success', 'Student index removed.');
    }
}
