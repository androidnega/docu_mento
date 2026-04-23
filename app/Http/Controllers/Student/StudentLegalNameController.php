<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StudentLegalNameController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $user = Auth::user();
        if (! $user instanceof User || ! $user->isDocuMentorStudent()) {
            return redirect()->route('dashboard');
        }

        $student = Student::findForDocuMentorUser($user);
        if (! $student) {
            return redirect()->route('dashboard');
        }

        if ($student->legal_name_completed_at !== null) {
            return redirect()->route('dashboard');
        }

        [$first, $last] = self::splitNameParts($student->student_name);

        return view('student.legal-name', [
            'firstName' => old('first_name', $first),
            'lastName' => old('last_name', $last),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user instanceof User || ! $user->isDocuMentorStudent()) {
            return redirect()->route('dashboard');
        }

        $student = Student::findForDocuMentorUser($user);
        if (! $student) {
            return redirect()->route('dashboard');
        }

        if ($student->legal_name_completed_at !== null) {
            return redirect()->route('dashboard');
        }

        $data = $request->validate([
            'first_name' => ['required', 'string', 'min:1', 'max:80'],
            'last_name' => ['required', 'string', 'min:1', 'max:80'],
        ]);

        $first = self::normalizeNamePart($data['first_name']);
        $last = self::normalizeNamePart($data['last_name']);
        $full = trim($first.' '.$last);

        if ($full === '') {
            return back()->withErrors(['first_name' => 'Please enter your first and last name.'])->withInput();
        }

        $idx = trim((string) ($student->index_number ?? ''));
        if (User::docuMentorNameIsIndexNumber($full, $idx, null)) {
            return back()->withErrors(['first_name' => 'Please use your real name, not your index number.'])->withInput();
        }

        $student->student_name = $full;
        $student->legal_name_completed_at = now();
        $student->save();

        User::propagateDocuMentorDisplayNameFromStudent($student);

        return redirect()->route('dashboard')->with('success', 'Thanks, '.$first.'. Your profile is updated.');
    }

    /**
     * @return array{0: string, 1: string}
     */
    public static function splitNameParts(?string $name): array
    {
        $n = trim((string) $name);
        if ($n === '') {
            return ['', ''];
        }
        $words = preg_split('/\s+/u', $n, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($words === []) {
            return ['', ''];
        }
        if (count($words) === 1) {
            return [$words[0], ''];
        }
        $first = array_shift($words);

        return [$first, implode(' ', $words)];
    }

    private static function normalizeNamePart(string $value): string
    {
        $t = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return $t === '' ? '' : Str::title(Str::lower($t));
    }
}
