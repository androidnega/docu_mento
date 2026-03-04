<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;

class StudentDashboardController extends Controller
{
    /**
     * Student / Leader dashboard. Single route: load user, academic year, group, project (if any).
     * Decision tree: No group → Create group; Group, no project → Create project; Project exists → overview (status, supervisors, deadline, chapters, proposals).
     */
    public function index(): View
    {
        $user = auth()->user();
        $displayName = $user && method_exists($user, 'name') ? ($user->name ?: $user->email ?? 'User') : 'User';
        $student = null;
        if ($user && method_exists($user, 'index_number') && trim((string) ($user->index_number ?? '')) !== '') {
            $student = Student::where('index_number_hash', Student::hashIndexNumber($user->index_number))->first();
        }

        $hasProjectAccess = false;
        $isGroupLeader = false;
        $leaderWithoutGroup = false;
        $leaderHasProject = false;
        $docuMentorGroup = null;
        $leaderProject = null;
        $academicYear = null;
        $projectDeadline = null;

        if ($user instanceof User) {
            $isGroupLeader = (bool) User::where('id', $user->id)->value('group_leader');
            $hasProjectAccess = $user->isDocuMentorStudent() || $user->isStudentRole() || $isGroupLeader;
            $leaderWithoutGroup = $isGroupLeader && $user->ledDocuMentorGroups()->doesntExist();
            if ($isGroupLeader) {
                $leaderHasProject = $user->ledDocuMentorGroups()->whereHas('project')->exists();
            }
            $leaderGroup = $user->ledDocuMentorGroups()->with(['project', 'academicYear', 'members'])->first();
            $memberGroup = $user->docuMentorGroups()->with(['project', 'academicYear', 'members'])->first();
            $docuMentorGroup = $leaderGroup ?: $memberGroup;

            // Leader project overview: project (if exists) with supervisors, chapters (and submissions for progress), proposals for dashboard
            if ($leaderGroup && $leaderGroup->project) {
                $leaderProject = $leaderGroup->project;
                $leaderProject->load(['supervisors', 'chapters' => fn ($q) => $q->orderBy('order')->with('submissions'), 'academicYear', 'proposals', 'category']);
                $academicYear = $leaderProject->academicYear ?? $leaderGroup->academicYear;
                $projectDeadline = $leaderProject->submission_deadline
                    ?? ($academicYear ? $academicYear->effective_deadline : null);
            } elseif ($docuMentorGroup) {
                $academicYear = $docuMentorGroup->academicYear;
            }
        }

        [$greeting, $holidayBadge] = $this->buildDashboardGreeting($user, $student);

        return view('student.dashboard.index', [
            'user' => $user,
            'student' => $student,
            'displayName' => $displayName,
            'greeting' => $greeting,
            'holidayBadge' => $holidayBadge,
            'hasProjectAccess' => $hasProjectAccess,
            'isGroupLeader' => $isGroupLeader,
            'leaderWithoutGroup' => $leaderWithoutGroup,
            'leaderHasProject' => $leaderHasProject,
            'docuMentorGroup' => $docuMentorGroup,
            'leaderProject' => $leaderProject,
            'academicYear' => $academicYear,
            'projectDeadline' => $projectDeadline,
        ]);
    }

    /**
     * Build a friendly greeting for the student dashboard.
     * Sometimes uses Twi and the student's last name, and shows holiday wishes on Ghanaian public holidays.
     *
     * @return array{0:string,1:?array}
     */
    private function buildDashboardGreeting(?User $user, ?Student $student): array
    {
        $lastName = $this->extractLastName($user, $student);
        $now = Carbon::now('Africa/Accra');
        $hour = (int) $now->format('H');

        $holiday = $this->ghanaHolidayForDate($now);
        $useTwi = $holiday !== null || random_int(1, 100) <= 45;

        if ($useTwi) {
            if ($holiday) {
                $greeting = trim($holiday['twi_greeting'] . ' ' . $lastName . '!');
                $badge = [
                    'message' => $holiday['twi_badge'],
                    'icon' => $holiday['icon'],
                    'bg' => $holiday['bg'],
                    'text' => $holiday['text'],
                ];
                return [$greeting, $badge];
            }

            if ($hour < 12) {
                $base = 'Mema wo akye';
            } elseif ($hour < 18) {
                $base = 'Mema wo aha';
            } else {
                $base = 'Mema wo adwo';
            }

            $extras = [
                'Ɛte sɛn?',
                'Ɛyɛ dɛn na adwuma rekɔ?',
                'Hyɛ den, adwuma no bɛkɔ yiye.',
                'Meda wo akye na menam so wish wo adwuma pa.',
            ];
            $extra = $extras[array_rand($extras)];

            $greeting = $base . ', ' . $lastName . '. ' . $extra;
            return [$greeting, null];
        }

        $timeGreeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
        $greeting = $timeGreeting . ', ' . $lastName . '.';

        if ($holiday) {
            $badge = [
                'message' => $holiday['badge_en'],
                'icon' => $holiday['icon'],
                'bg' => $holiday['bg'],
                'text' => $holiday['text'],
            ];
            return [$greeting, $badge];
        }

        return [$greeting, null];
    }

    private function extractLastName(?User $user, ?Student $student): string
    {
        $nameSource = null;
        if ($student && $student->student_name) {
            $nameSource = $student->student_name;
        } elseif ($user && $user->name) {
            $nameSource = $user->name;
        } elseif ($user && $user->username) {
            $nameSource = $user->username;
        }

        if (! $nameSource) {
            return 'Student';
        }

        $parts = preg_split('/\s+/', trim((string) $nameSource)) ?: [];
        $last = end($parts) ?: reset($parts);

        return $last ?: 'Student';
    }

    /**
     * Very small set of Ghana public holidays with Twi/English messages and icons.
     *
     * @return array<string,string>|null
     */
    private function ghanaHolidayForDate(Carbon $date): ?array
    {
        $md = $date->format('m-d');

        return match ($md) {
            '01-01' => [
                'key' => 'new_year',
                'twi_greeting' => 'Afahyia pa',
                'twi_badge' => 'Afahyia pa! Ma w’adwuma ne adesua mmra mu nkɔso.',
                'badge_en' => 'Happy New Year! Wishing you a focused semester.',
                'icon' => 'fas fa-sun',
                'bg' => 'bg-amber-100',
                'text' => 'text-amber-900',
            ],
            '03-06' => [
                'key' => 'independence_day',
                'twi_greeting' => 'Anigye Ghana gye ne ho da',
                'twi_badge' => 'Anigye da! Ma wo projekti mmere adesua ne adwuma adi anim.',
                'badge_en' => 'Happy Independence Day! Push your project forward.',
                'icon' => 'fas fa-flag',
                'bg' => 'bg-emerald-100',
                'text' => 'text-emerald-800',
            ],
            '05-01' => [
                'key' => 'workers_day',
                'twi_greeting' => 'Ayekoo adwumayɛ',
                'twi_badge' => 'Ayekoo! Fa kakra gye ho na san to wo ho so.',
                'badge_en' => 'Happy Workers’ Day! Take a breather and keep going.',
                'icon' => 'fas fa-briefcase',
                'bg' => 'bg-sky-100',
                'text' => 'text-sky-800',
            ],
            '12-25' => [
                'key' => 'christmas',
                'twi_greeting' => 'Afhyia pa na Afehyia pa',
                'twi_badge' => 'Afhyia pa! Feier na fa bere ketewa sua kakra.',
                'badge_en' => 'Merry Christmas! Enjoy the break and rest well.',
                'icon' => 'fas fa-gift',
                'bg' => 'bg-red-100',
                'text' => 'text-red-800',
            ],
            '12-26' => [
                'key' => 'boxing_day',
                'twi_greeting' => 'Akɔnnɔ da pa',
                'twi_badge' => 'Akɔnnɔ da pa! Fa bere yi hyɛ wo ho den ma afe foforo no.',
                'badge_en' => 'Happy Boxing Day! Recharge for the next term.',
                'icon' => 'fas fa-box',
                'bg' => 'bg-slate-100',
                'text' => 'text-slate-800',
            ],
            default => null,
        };
    }

    public function profile(): View
    {
        $user = auth()->user();
        $student = $user && method_exists($user, 'index_number') ? Student::where('index_number_hash', Student::hashIndexNumber($user->index_number ?? ''))->first() : null;
        if (! $user) {
            abort(403, 'Not logged in.');
        }
        return view('student.dashboard.profile', compact('student'));
    }

    public function updateProfile(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();
        $student = $user && method_exists($user, 'index_number') ? Student::where('index_number_hash', Student::hashIndexNumber($user->index_number ?? ''))->first() : null;
        if (! $user) {
            abort(403, 'Not logged in.');
        }
        if (! $student) {
            return redirect()->route('dashboard')->with('info', 'Profile is managed from your user account.');
        }
        $request->validate(['student_name' => 'nullable|string|max:255']);
        $student->student_name = $request->filled('student_name') ? ucwords(strtolower(trim($request->student_name))) : $student->student_name;
        $student->save();
        return redirect()->route('dashboard.my-profile')->with('success', 'Profile updated.');
    }

    public function calendar(): View
    {
        $user = auth()->user();
        $student = $user && method_exists($user, 'index_number') ? Student::where('index_number_hash', Student::hashIndexNumber($user->index_number ?? ''))->first() : null;
        if (! $user) {
            abort(403, 'Not logged in.');
        }
        return view('student.dashboard.calendar', compact('student'));
    }

    public function courseMaterials(): View
    {
        $user = auth()->user();
        if (! $user) {
            abort(403, 'Not logged in.');
        }
        return view('student.dashboard.materials');
    }
}
