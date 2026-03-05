<?php

use App\Http\Controllers\MigrateSqliteToMysqlController;
use App\Http\Controllers\RunMigrationsController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\StudentController as CoreStudentController;
use App\Http\Controllers\VordinatorController as CoreVordinatorController;
use App\Http\Controllers\SupervisorController as CoreSupervisorController;
use App\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

// SQLite → MySQL migration (run via URL with secret key; no auth)
Route::get('/migrate-sqlite-to-mysql', MigrateSqliteToMysqlController::class)->name('migrate.sqlite.to.mysql');
// Run normal pending Laravel migrations via URL with secret key (no data import).
// Creates/updates all tables (e.g. exam_calendar). See MIGRATION-LINK.md.
// Link: https://your-domain/migration?key=YOUR_SECRET (default key: DocuMentoMigrate2026Xp9k3m7)
Route::get('/run-migrations', RunMigrationsController::class)->name('migrate.run.pending');
Route::get('/migration', RunMigrationsController::class)->name('migration');
// Short link: https://your-domain/themigration?key=YOUR_SECRET
Route::get('/themigration', RunMigrationsController::class)->name('migration.short');
// Timeout probe for dashboard (removed)
// Clear caches via URL (fix "pushed but not showing on live") – same key as run-migrations
// Use: https://YOUR-SITE.com/clear-cache?key=DocuMentoMigrate2026Xp9k3m7 (no .php)
Route::get('/clear-cache', \App\Http\Controllers\ClearCacheController::class)->name('clear.cache');
Route::get('/clear-cache.php', \App\Http\Controllers\ClearCacheController::class);
// Seed group_names (Docu Mentor create-group names). Same key as migration.
// Use: https://your-domain/seed-group-names?key=YOUR_SECRET
Route::get('/seed-group-names', \App\Http\Controllers\SeedGroupNamesController::class)->name('seed.group-names');
// Maintenance: list helper URLs (no key) – use to verify routes are deployed on live
Route::get('/maintenance', [\App\Http\Controllers\FixPullController::class, 'maintenance'])->name('maintenance');
// Fix git pull merge error (same key as run-migrations)
Route::get('/fix-pull', [\App\Http\Controllers\FixPullController::class, 'show'])->name('fix.pull');
Route::get('/fix-pull/run', [\App\Http\Controllers\FixPullController::class, 'run'])->name('fix.pull.run');
Route::get('/fix-pull/script', [\App\Http\Controllers\FixPullController::class, 'script'])->name('fix.pull.script');
// Short link: your-domain/thekey?key=YOUR_SECRET — runs fix-pull (no SSH needed)
Route::get('/thekey', [\App\Http\Controllers\FixPullController::class, 'run'])->name('fix.pull.thekey');
// Docu Mentor proposal diagnostics (no SSH/terminal): your-domain/thetoken?key=YOUR_SECRET — optional: &project=ID&limit=10
Route::get('/thetoken', [\App\Http\Controllers\DocuMentorProposalDiagnosticsController::class, '__invoke'])->name('docu-mentor.diagnostics.thetoken');

// Docu Mentor – support docu_mentor (underscore) URLs, redirect to docu-mentor (hyphen)
Route::redirect('/docu_mentor', '/docu-mentor', 301);
Route::get('/docu_mentor/{any?}', function (?string $any = '') {
    return redirect('/docu-mentor' . ($any ? '/' . $any : ''), 301);
})->where('any', '.*');
// Docu Mentor uses unified /login. Redirect docu-mentor/login and docu_mentor/login to /login.
Route::redirect('/docu-mentor/login', '/login', 301)->name('docu-mentor.login');
Route::redirect('/docu_mentor/login', '/login', 301);

// Supervisor pages: redirect old /docu-mentor/supervisors/* to /dashboard/supervisor/*
Route::redirect('/docu-mentor/supervisors', '/dashboard/supervisor/projects', 301);
Route::get('/docu-mentor/supervisors/{any}', function (string $any) {
    return redirect('/dashboard/supervisor/' . $any, 301);
})->where('any', '.*');

Route::middleware(['docu-mentor.auth', 'docu-mentor.project-access'])->prefix('docu-mentor')->name('docu-mentor.')->group(function () {
    Route::get('/', [\App\Http\Controllers\DocuMentor\DocuMentorDashboardController::class, '__invoke'])->name('dashboard');

    // Students – redirect to unified dashboard
    Route::get('/students', fn () => redirect()->route('dashboard', [], 301))->name('students.dashboard');
    Route::middleware('docu-mentor.student')->group(function () {
        Route::get('/students/join-group', fn () => redirect()->route('dashboard.projects.index')->with('info', 'Only your group leader adds members.'))->name('students.join-group');
        Route::post('/students/join-group', fn () => redirect()->route('dashboard.projects.index')->with('info', 'Only your group leader adds members.'));
        Route::get('/students/projects', [\App\Http\Controllers\DocuMentor\StudentProjectController::class, 'index'])->name('students.projects.index');
        Route::get('/students/projects/create', [\App\Http\Controllers\DocuMentor\StudentProjectController::class, 'create'])->name('students.projects.create');
        Route::post('/students/projects', [\App\Http\Controllers\DocuMentor\StudentProjectController::class, 'store'])->name('students.projects.store');
        Route::post('/students/projects/proposals/upload-temp', [\App\Http\Controllers\DocuMentor\StudentTempProposalUploadController::class, '__invoke'])->name('students.projects.proposals.upload-temp');
        Route::get('/students/projects/{project}', [\App\Http\Controllers\DocuMentor\StudentProjectController::class, 'show'])->name('students.projects.show');
        Route::post('/students/projects/{project}/features', [\App\Http\Controllers\DocuMentor\StudentFeatureController::class, 'store'])->name('students.projects.features.store');
        Route::put('/students/projects/{project}/features/{feature}', [\App\Http\Controllers\DocuMentor\StudentFeatureController::class, 'update'])->name('students.projects.features.update');
        Route::delete('/students/projects/{project}/features/{feature}', [\App\Http\Controllers\DocuMentor\StudentFeatureController::class, 'destroy'])->name('students.projects.features.destroy');
        Route::post('/students/projects/{project}/proposals', [\App\Http\Controllers\DocuMentor\StudentProposalController::class, 'store'])->name('students.proposals.store');
        Route::get('/students/public-projects', [\App\Http\Controllers\DocuMentor\PublicProjectController::class, 'index'])->name('students.public-projects');
        Route::post('/students/group/add-member', [\App\Http\Controllers\DocuMentor\GroupLeaderController::class, 'addMember'])->name('students.group.add-member');
        Route::get('/students/group/{group}', [\App\Http\Controllers\DocuMentor\GroupLeaderController::class, 'showGroup'])->name('students.group.show');
        Route::post('/students/group/{group}/remove/{member}', [\App\Http\Controllers\DocuMentor\GroupLeaderController::class, 'removeMember'])->name('students.group.remove-member');
        Route::post('/students/projects/{project}/chapters/{chapter}/submissions', [\App\Http\Controllers\DocuMentor\StudentSubmissionController::class, 'store'])->name('students.submissions.store');
    });

    // Legacy: redirect old coordinator URLs to unified /dashboard/coordinators/...
    Route::redirect('/coordinators', '/dashboard', 301);
    Route::get('/coordinators/{any}', function (string $any) {
        return redirect('/dashboard/coordinators/' . $any, 301);
    })->where('any', '.*');
});

// 7 PROJECT PUBLIC PAGE – URL /projects. Display: Title, Description, Features, Budget, Supervisors. Filter by: Academic Year, Category, Supervisor.
Route::get('/projects', [\App\Http\Controllers\DocuMentor\PublicProjectController::class, 'index'])->name('public.projects.index');

// Public homepage – Docu Mento welcome (hero, about, features, CTA, footer)
Route::get('/', function () {
    return view('welcome', ['student' => null]);
})->name('student.landing');

Route::get('/about-system', function () {
    return view('student.about-system', ['student' => null]);
})->name('about-system');

// Student login handled by account login routes below

// Student account login (index → phone → OTP)
Route::get('/student/account/login', [\App\Http\Controllers\Student\StudentAccountController::class, 'showLoginForm'])->name('student.account.login.form');
// Backwards-compatible alias for older views using route('student.login.form')
Route::get('/student/login', function () {
    return redirect()->route('student.account.login.form');
})->name('student.login.form');
Route::post('/student/account/verify-index', [\App\Http\Controllers\Student\StudentAccountController::class, 'verifyIndex'])->name('student.account.verify-index');
Route::post('/student/account/send-otp', [\App\Http\Controllers\Student\StudentAccountController::class, 'sendOtp'])->name('student.account.send-otp');
Route::post('/student/account/verify-otp', [\App\Http\Controllers\Student\StudentAccountController::class, 'verifyOtp'])->name('student.account.verify-otp');
Route::post('/student/account/logout', [\App\Http\Controllers\Student\StudentAccountController::class, 'logout'])->name('student.account.logout');

// Student passkey (WebAuthn) — students only; not for staff/admin
Route::post('/student/account/passkey/login-options', [\App\Http\Controllers\Student\StudentWebAuthnController::class, 'loginOptions'])->name('student.passkey.login-options');
Route::post('/student/account/passkey/login', [\App\Http\Controllers\Student\StudentWebAuthnController::class, 'login'])->name('student.passkey.login');
Route::post('/student/account/passkey/register-options', [\App\Http\Controllers\Student\StudentWebAuthnController::class, 'registerOptions'])->name('student.passkey.register-options');
Route::post('/student/account/passkey/register', [\App\Http\Controllers\Student\StudentWebAuthnController::class, 'register'])->name('student.passkey.register');

// Student dashboard (Docu Mento only) — students use same /login and /dashboard as other roles
Route::middleware(['dashboard.auth', 'student.auth', 'student.has-level'])->group(function () {
    Route::get('/student/dashboard', [CoreStudentController::class, 'index'])->name('student.dashboard');
});

// Unified dashboard: /dashboard — single auth, view by role
Route::get('/dashboard', [\App\Http\Controllers\DashboardGatewayController::class, '__invoke'])->middleware(['auth'])->name('dashboard');
Route::middleware(['auth', 'role:student,group_leader'])->prefix('dashboard')->name('dashboard.')->group(function () {
    // Docu Mento-only mode
    Route::get('/my-profile', [\App\Http\Controllers\Student\StudentDashboardController::class, 'profile'])->name('my-profile');
    Route::put('/my-profile', [\App\Http\Controllers\Student\StudentDashboardController::class, 'updateProfile'])->name('my-profile.update');
    Route::get('/calendar', [\App\Http\Controllers\Student\StudentDashboardController::class, 'calendar'])->name('calendar');
    Route::get('/materials', [\App\Http\Controllers\Student\StudentDashboardController::class, 'courseMaterials'])->name('materials');
    Route::post('/push-subscribe', [\App\Http\Controllers\Student\PushSubscribeController::class, 'store'])->name('push-subscribe');
    Route::get('/documents', [\App\Http\Controllers\Student\StudentDocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [\App\Http\Controllers\Student\StudentDocumentController::class, 'store'])->name('documents.store');
});

// Project (student) routes under /dashboard — role:student,group_leader + policy for project access
Route::middleware(['auth', 'role:student,group_leader', 'docu-mentor.project-access'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/csrf-refresh', fn () => response()->json(['token' => csrf_token()]))->name('csrf-refresh');
    Route::get('/projects', [\App\Http\Controllers\DocuMentor\StudentProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/create', [\App\Http\Controllers\DocuMentor\StudentProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [\App\Http\Controllers\DocuMentor\StudentProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [\App\Http\Controllers\DocuMentor\StudentProjectController::class, 'show'])->name('projects.show');
    Route::put('/projects/{project}', [\App\Http\Controllers\DocuMentor\StudentProjectController::class, 'update'])->name('projects.update');
    Route::post('/projects/{project}/features', [\App\Http\Controllers\DocuMentor\StudentFeatureController::class, 'store'])->name('projects.features.store');
    Route::put('/projects/{project}/features/{feature}', [\App\Http\Controllers\DocuMentor\StudentFeatureController::class, 'update'])->name('projects.features.update');
    Route::delete('/projects/{project}/features/{feature}', [\App\Http\Controllers\DocuMentor\StudentFeatureController::class, 'destroy'])->name('projects.features.destroy');
    Route::post('/projects/{project}/proposals', [\App\Http\Controllers\DocuMentor\StudentProposalController::class, 'store'])->name('projects.proposals.store');
    Route::get('/projects/{project}/proposals/{proposal}/download', [\App\Http\Controllers\DocuMentor\SupervisorFileController::class, 'downloadProposal'])->name('projects.proposals.download');
    Route::get('/join-group', fn () => redirect()->route('dashboard.projects.index')->with('info', 'Only your group leader adds members.'))->name('join-group');
    Route::post('/join-group', fn () => redirect()->route('dashboard.projects.index')->with('info', 'Only your group leader adds members.'));
    Route::get('/public-projects', [\App\Http\Controllers\DocuMentor\PublicProjectController::class, 'index'])->name('public-projects');
    Route::get('/group/create', [\App\Http\Controllers\DocuMentor\GroupLeaderController::class, 'createGroup'])->name('group.create');
    Route::post('/group', [\App\Http\Controllers\DocuMentor\GroupLeaderController::class, 'storeGroup'])->name('group.store');
    Route::post('/group/add-member', [\App\Http\Controllers\DocuMentor\GroupLeaderController::class, 'addMember'])->name('group.add-member');
    Route::get('/group/{group}', [\App\Http\Controllers\DocuMentor\GroupLeaderController::class, 'showGroup'])->name('group.show');
    Route::post('/group/{group}/remove/{member}', [\App\Http\Controllers\DocuMentor\GroupLeaderController::class, 'removeMember'])->name('group.remove-member');
    Route::post('/projects/{project}/chapters/{chapter}/submissions', [\App\Http\Controllers\DocuMentor\StudentSubmissionController::class, 'store'])->name('projects.submissions.store');
});

// Staff login (rate-limited to 5 attempts per minute per IP+username)
Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:login')->name('login.post');
Route::get('/password/forgot', [\App\Http\Controllers\Admin\StaffPasswordResetController::class, 'showForgotForm'])->name('password.forgot');
Route::post('/password/forgot', [\App\Http\Controllers\Admin\StaffPasswordResetController::class, 'sendResetLink'])->name('password.forgot.send');
Route::get('/password/reset/{token}', [\App\Http\Controllers\Admin\StaffPasswordResetController::class, 'showResetForm'])->name('password.reset.form');
Route::post('/password/reset', [\App\Http\Controllers\Admin\StaffPasswordResetController::class, 'reset'])->name('password.reset');

// Staff dashboard and all staff pages under /dashboard (admin + supervisor)
Route::middleware('admin.auth')->group(function () {
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
    Route::get('/logout', [AdminAuthController::class, 'logout'])->name('logout.get');
    // Role-specific dashboards
    Route::middleware('docu-mentor.coordinator')->group(function () {
        Route::get('/vordinator/dashboard', [CoreVordinatorController::class, 'index'])->name('vordinator.dashboard');
    });

    Route::middleware('docu-mentor.supervisor')->group(function () {
        Route::get('/supervisor/dashboard', [CoreSupervisorController::class, 'index'])->name('supervisor.dashboard');
    });

    // GET /dashboard is handled by DashboardGatewayController (unified)

        Route::prefix('dashboard')->name('dashboard.')->middleware('block.superadmin.coordinator')->group(function () {
        // Minimal ping (same auth/session as dashboard)
        Route::get('/ping', fn () => response('OK', 200, ['Content-Type' => 'text/plain; charset=utf-8']))->name('ping');
        // Profile — both roles
        Route::get('/profile', [\App\Http\Controllers\Admin\StaffProfileController::class, 'show'])->name('profile.show');
        Route::put('/profile', [\App\Http\Controllers\Admin\StaffProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/avatar', [\App\Http\Controllers\Admin\StaffProfileController::class, 'updateAvatar'])->name('profile.avatar');
        Route::get('/profile/password', [\App\Http\Controllers\Admin\StaffProfileController::class, 'password'])->name('profile.password');
        Route::put('/profile/password', [\App\Http\Controllers\Admin\StaffProfileController::class, 'updatePassword'])->name('profile.password.update');

        // Supervisors can edit their own profile (school/department or legacy institution/faculty)
        Route::get('/users/{user}/edit', [\App\Http\Controllers\Admin\UserManagementController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [\App\Http\Controllers\Admin\UserManagementController::class, 'update'])->name('users.update');

        // School/Department AJAX (for user management and profile)
        Route::get('/schools/{school}/departments', [\App\Http\Controllers\Admin\DepartmentController::class, 'bySchool'])->name('departments.by-school');

        // Class groups (coordinator + supervisors)
        Route::get('/class-groups', [\App\Http\Controllers\Admin\ClassGroupController::class, 'index'])->name('class-groups.index');
        Route::get('/class-groups/create', [\App\Http\Controllers\Admin\ClassGroupController::class, 'create'])->name('class-groups.create');
        Route::post('/class-groups', [\App\Http\Controllers\Admin\ClassGroupController::class, 'store'])->name('class-groups.store');
        Route::get('/class-groups/{classGroup}', [\App\Http\Controllers\Admin\ClassGroupController::class, 'show'])->name('class-groups.show');
        Route::get('/class-groups/{classGroup}/edit', [\App\Http\Controllers\Admin\ClassGroupController::class, 'edit'])->name('class-groups.edit');
        Route::put('/class-groups/{classGroup}', [\App\Http\Controllers\Admin\ClassGroupController::class, 'update'])->name('class-groups.update');
        Route::delete('/class-groups/{classGroup}', [\App\Http\Controllers\Admin\ClassGroupController::class, 'destroy'])->name('class-groups.destroy');
        Route::put('/class-groups/{classGroup}/allowed-devices', [\App\Http\Controllers\Admin\ClassGroupController::class, 'updateAllowedDevices'])->name('class-groups.allowed-devices.update');

        // Class group students management
        Route::get('/class-groups/{classGroup}/students', [\App\Http\Controllers\Admin\ClassGroupController::class, 'studentsIndex'])->name('class-groups.students.index');
        Route::post('/class-groups/{classGroup}/students', [\App\Http\Controllers\Admin\ClassGroupController::class, 'addStudent'])->name('class-groups.students.add');
        Route::post('/class-groups/{classGroup}/students/upload', [\App\Http\Controllers\Admin\ClassGroupController::class, 'uploadStudents'])->name('class-groups.students.upload');
        Route::get('/class-groups/{classGroup}/students/export/excel', [\App\Http\Controllers\Admin\ClassGroupController::class, 'exportStudentsExcel'])->name('class-groups.students.export.excel');
        Route::get('/class-groups/{classGroup}/students/export/pdf', [\App\Http\Controllers\Admin\ClassGroupController::class, 'exportStudentsPdf'])->name('class-groups.students.export.pdf');
        Route::delete('/class-groups/{classGroup}/students/bulk-destroy', [\App\Http\Controllers\Admin\ClassGroupController::class, 'bulkDestroyStudents'])->name('class-groups.students.bulk-destroy');
        Route::post('/class-groups/{classGroup}/students/clear', [\App\Http\Controllers\Admin\ClassGroupController::class, 'clearStudents'])->name('class-groups.students.clear');
        Route::get('/class-groups/{classGroup}/students/{student}', [\App\Http\Controllers\Admin\ClassGroupController::class, 'showStudent'])->name('class-groups.students.show');
        Route::get('/class-groups/{classGroup}/students/{student}/edit', [\App\Http\Controllers\Admin\ClassGroupController::class, 'editStudent'])->name('class-groups.students.edit');
        Route::put('/class-groups/{classGroup}/students/{student}', [\App\Http\Controllers\Admin\ClassGroupController::class, 'updateStudent'])->name('class-groups.students.update');
        Route::delete('/class-groups/{classGroup}/students/{student}', [\App\Http\Controllers\Admin\ClassGroupController::class, 'destroyStudent'])->name('class-groups.students.destroy');
        Route::post('/class-groups/{classGroup}/students/{student}/remove-phone', [\App\Http\Controllers\Admin\ClassGroupController::class, 'removeStudentPhone'])->name('class-groups.students.remove-phone');
        Route::post('/class-groups/{classGroup}/students/{student}/fallback-code', [\App\Http\Controllers\Admin\ClassGroupController::class, 'generateFallbackCode'])->name('class-groups.students.fallback-code');

        // Coordinator only: Docu Mentor under unified /dashboard/coordinators/academic-years, /dashboard/coordinators/categories, etc.
        Route::middleware('docu-mentor.coordinator')->prefix('coordinators')->name('coordinators.')->group(function () {
            Route::get('academic-years/{academicYear}/students', [\App\Http\Controllers\DocuMentor\CoordinatorStudentController::class, 'studentsByYear'])->name('academic-years.students');
            Route::get('academic-years/{academicYear}/supervisors', [\App\Http\Controllers\DocuMentor\CoordinatorStudentController::class, 'supervisorsByYear'])->name('academic-years.supervisors');
            Route::get('students/list', [\App\Http\Controllers\DocuMentor\CoordinatorStudentController::class, 'studentsList'])->name('students.list');
            Route::get('supervisors', [\App\Http\Controllers\DocuMentor\CoordinatorStudentController::class, 'supervisorsIndex'])->name('supervisors.index');
            Route::get('supervisors/list', [\App\Http\Controllers\DocuMentor\CoordinatorStudentController::class, 'supervisorsList'])->name('supervisors.list');
            Route::resource('academic-years', \App\Http\Controllers\DocuMentor\AcademicYearController::class)->parameters(['academic-years' => 'academicYear']);
            Route::resource('categories', \App\Http\Controllers\DocuMentor\CategoryController::class);
            Route::resource('semesters', \App\Http\Controllers\Admin\SemesterController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
            Route::resource('academic-classes', \App\Http\Controllers\Admin\AcademicClassController::class)->parameters(['academic-classes' => 'academicClass'])->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
            Route::get('groups', [\App\Http\Controllers\DocuMentor\ProjectGroupController::class, 'index'])->name('groups.index');
            Route::get('groups/{group}', [\App\Http\Controllers\DocuMentor\ProjectGroupController::class, 'show'])->name('groups.show');
            Route::post('groups/{group}/members', [\App\Http\Controllers\DocuMentor\ProjectGroupController::class, 'addMember'])->name('groups.members.store');
            Route::delete('groups/{group}', [\App\Http\Controllers\DocuMentor\ProjectGroupController::class, 'destroy'])->name('groups.destroy');
            Route::delete('groups/{group}/members/{member}', [\App\Http\Controllers\DocuMentor\ProjectGroupController::class, 'removeMember'])->name('groups.members.remove');
            Route::get('assign-group-leaders', [\App\Http\Controllers\DocuMentor\AssignGroupLeaderController::class, 'index'])->name('assign-group-leaders.index');
            Route::post('assign-group-leaders/add', [\App\Http\Controllers\DocuMentor\AssignGroupLeaderController::class, 'add'])->name('assign-group-leaders.add');
            Route::post('assign-group-leaders/toggle/{user}', [\App\Http\Controllers\DocuMentor\AssignGroupLeaderController::class, 'toggle'])->name('assign-group-leaders.toggle');
            Route::post('assign-group-leaders/upload', [\App\Http\Controllers\DocuMentor\AssignGroupLeaderController::class, 'upload'])->name('assign-group-leaders.upload');
            Route::get('projects', [\App\Http\Controllers\DocuMentor\CoordinatorProjectController::class, 'index'])->name('projects.index');
            // More specific routes first so download is matched before projects/{project}
            Route::get('projects/{project}/proposals/{proposal}/download', [\App\Http\Controllers\DocuMentor\SupervisorFileController::class, 'downloadProposal'])->name('projects.proposals.download');
            Route::post('projects/{project}/proposals/{proposal}/comment', [\App\Http\Controllers\DocuMentor\CoordinatorProjectController::class, 'commentProposal'])->name('projects.proposals.comment');
            Route::post('projects/{project}/supervisors', [\App\Http\Controllers\DocuMentor\CoordinatorProjectController::class, 'addSupervisor'])->name('projects.supervisors.store');
            Route::get('projects/{project}', [\App\Http\Controllers\DocuMentor\CoordinatorProjectController::class, 'show'])->name('projects.show');
            Route::put('projects/{project}', [\App\Http\Controllers\DocuMentor\CoordinatorProjectController::class, 'update'])->name('projects.update');
            Route::post('projects/{project}/approve', [\App\Http\Controllers\DocuMentor\CoordinatorProjectController::class, 'approve'])->name('projects.approve');
            Route::post('projects/{project}/reject', [\App\Http\Controllers\DocuMentor\CoordinatorProjectController::class, 'reject'])->name('projects.reject');
            Route::delete('projects/{project}', [\App\Http\Controllers\DocuMentor\CoordinatorProjectController::class, 'destroy'])->name('projects.destroy');
            Route::post('projects/{project}/alert', [\App\Http\Controllers\DocuMentor\CoordinatorProjectController::class, 'alertProject'])->name('projects.alert');
            Route::post('projects/{project}/chapters', [\App\Http\Controllers\DocuMentor\CoordinatorProjectController::class, 'storeChapter'])->name('projects.chapters.store');
            Route::get('workload', [\App\Http\Controllers\DocuMentor\CoordinatorProjectController::class, 'workload'])->name('workload');
            Route::get('export-report', [\App\Http\Controllers\DocuMentor\CoordinatorProjectController::class, 'exportReportPage'])->name('export-report');
            Route::get('export-report/download', [\App\Http\Controllers\DocuMentor\CoordinatorProjectController::class, 'exportReport'])->name('export-report.download');
            Route::get('students', [\App\Http\Controllers\DocuMentor\CoordinatorStudentController::class, 'index'])->name('students.index');
            Route::post('students', [\App\Http\Controllers\DocuMentor\CoordinatorStudentController::class, 'store'])->name('students.store');
            Route::post('students/upload', [\App\Http\Controllers\DocuMentor\CoordinatorStudentController::class, 'upload'])->name('students.upload');
            Route::delete('students/bulk-destroy', [\App\Http\Controllers\DocuMentor\CoordinatorStudentController::class, 'bulkDestroy'])->name('students.bulk-destroy');
            Route::post('students/bulk-destroy-selected', [\App\Http\Controllers\DocuMentor\CoordinatorStudentController::class, 'bulkDestroySelected'])->name('students.bulk-destroy-selected');
            Route::get('students/{encodedIndex}/edit', [\App\Http\Controllers\DocuMentor\CoordinatorStudentController::class, 'edit'])->name('students.edit');
            Route::post('students/{encodedIndex}/toggle-leader', [\App\Http\Controllers\DocuMentor\CoordinatorStudentController::class, 'toggleGroupLeader'])->name('students.toggle-leader');
            Route::get('students/{encodedIndex}', [\App\Http\Controllers\DocuMentor\CoordinatorStudentController::class, 'show'])->name('students.show');
            Route::put('students/{encodedIndex}', [\App\Http\Controllers\DocuMentor\CoordinatorStudentController::class, 'update'])->name('students.update');
            Route::delete('students/{encodedIndex}', [\App\Http\Controllers\DocuMentor\CoordinatorStudentController::class, 'destroy'])->name('students.destroy');
        });

        // Supervisor project area: /dashboard/supervisor/projects
        Route::middleware('docu-mentor.supervisor')->prefix('supervisor')->name('docu-mentor.')->group(function () {
            Route::get('/projects', [\App\Http\Controllers\DocuMentor\SupervisorProjectController::class, 'index'])->name('projects.index');
            Route::get('/projects/{project}', [\App\Http\Controllers\DocuMentor\SupervisorProjectController::class, 'show'])->name('projects.show');
            Route::get('/projects/{project}/chapters/{chapterOrder}', [\App\Http\Controllers\DocuMentor\SupervisorChapterController::class, 'show'])->name('chapters.show')->whereNumber('chapterOrder');
            Route::put('/projects/{project}/chapters/{chapterRef}', [\App\Http\Controllers\DocuMentor\SupervisorChapterController::class, 'update'])->name('chapters.update')->whereNumber('chapterRef');
            Route::post('/projects/{project}/chapters/{chapterRef}/toggle-open', [\App\Http\Controllers\DocuMentor\SupervisorChapterController::class, 'toggleOpen'])->name('chapters.toggle-open')->whereNumber('chapterRef');
            Route::post('/projects/{project}/chapters/{chapterRef}/mark-completed', [\App\Http\Controllers\DocuMentor\SupervisorChapterController::class, 'markCompleted'])->name('chapters.mark-completed')->whereNumber('chapterRef');
            Route::post('/projects/{project}/chapters/{chapterRef}/toggle-submissions', [\App\Http\Controllers\DocuMentor\SupervisorChapterController::class, 'toggleAllSubmissions'])->name('chapters.toggle-submissions')->whereNumber('chapterRef');
            Route::post('/projects/{project}/chapters/{chapterRef}/submissions', [\App\Http\Controllers\DocuMentor\SupervisorSubmissionController::class, 'store'])->name('submissions.store')->whereNumber('chapterRef');
            Route::put('/projects/{project}/chapters/{chapterRef}/submissions/{submission}', [\App\Http\Controllers\DocuMentor\SupervisorSubmissionController::class, 'update'])->name('submissions.update')->whereNumber('chapterRef');
            Route::delete('/projects/{project}/chapters/{chapterRef}/submissions/{submission}', [\App\Http\Controllers\DocuMentor\SupervisorSubmissionController::class, 'destroy'])->name('submissions.destroy')->whereNumber('chapterRef');
            Route::post('/projects/{project}/files', [\App\Http\Controllers\DocuMentor\SupervisorFileController::class, 'uploadProjectFiles'])->name('files.upload');
            Route::post('/projects/{project}/final-submission', [\App\Http\Controllers\DocuMentor\SupervisorFileController::class, 'uploadFinalSubmission'])->name('final-submission.upload');
            Route::get('/projects/{project}/proposals/{proposal}/download', [\App\Http\Controllers\DocuMentor\SupervisorFileController::class, 'downloadProposal'])->name('proposals.download');
            Route::get('/projects/{project}/download-final', [\App\Http\Controllers\DocuMentor\SupervisorFileController::class, 'downloadFinalSubmission'])->name('download-final');
            Route::get('/projects/{project}/download-all', [\App\Http\Controllers\DocuMentor\SupervisorFileController::class, 'downloadAll'])->name('download-all');
            Route::post('/projects/{project}/chapters/{chapterRef}/submissions/{submission}/ai-review', [\App\Http\Controllers\DocuMentor\SupervisorAiController::class, 'reviewSubmission'])->name('ai.review-submission')->whereNumber('chapterRef');
            Route::post('/projects/{project}/ai-summary', [\App\Http\Controllers\DocuMentor\SupervisorAiController::class, 'projectSummary'])->name('ai.summary');
            Route::post('/projects/{project}/approve', [\App\Http\Controllers\DocuMentor\SupervisorProjectController::class, 'approveProject'])->name('projects.approve');
            Route::post('/projects/{project}/scores', [\App\Http\Controllers\DocuMentor\SupervisorProjectController::class, 'storeScores'])->name('projects.scores.store');
            Route::get('/documents/{document}/download', [\App\Http\Controllers\Supervisor\SupervisorDocumentController::class, 'download'])->name('documents.download');
        });

        // Super Admin only: schools, departments, users, settings, system reset
        Route::middleware('admin.role')->group(function () {
            Route::get('/schools', [\App\Http\Controllers\Admin\SchoolController::class, 'index'])->name('schools.index');
            Route::get('/schools/create', [\App\Http\Controllers\Admin\SchoolController::class, 'create'])->name('schools.create');
            Route::post('/schools', [\App\Http\Controllers\Admin\SchoolController::class, 'store'])->name('schools.store');
            Route::get('/schools/{school}/edit', [\App\Http\Controllers\Admin\SchoolController::class, 'edit'])->name('schools.edit');
            Route::put('/schools/{school}', [\App\Http\Controllers\Admin\SchoolController::class, 'update'])->name('schools.update');
            Route::delete('/schools/{school}', [\App\Http\Controllers\Admin\SchoolController::class, 'destroy'])->name('schools.destroy');
            Route::post('/departments', [\App\Http\Controllers\Admin\DepartmentController::class, 'store'])->name('departments.store');
            Route::put('/departments/{department}', [\App\Http\Controllers\Admin\DepartmentController::class, 'update'])->name('departments.update');
            Route::delete('/departments/{department}', [\App\Http\Controllers\Admin\DepartmentController::class, 'destroy'])->name('departments.destroy');
            Route::post('/settings/update-mode', [SettingsController::class, 'toggleUpdateMode'])->name('settings.update-mode');
            Route::post('/settings/update-estimated-end', [SettingsController::class, 'setUpdateEstimatedEnd'])->name('settings.update-estimated-end');
            Route::get('/system/reset', [\App\Http\Controllers\Admin\SystemResetController::class, 'index'])->name('system.reset.index');
            Route::post('/system/reset', [\App\Http\Controllers\Admin\SystemResetController::class, 'reset'])->name('system.reset');
            Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
            if (! app()->environment('production')) {
                Route::get('/settings/ai-test', [SettingsController::class, 'aiTest'])->name('settings.ai-test');
                Route::get('/settings/cloudinary-test', [SettingsController::class, 'cloudinaryTest'])->name('settings.cloudinary-test');
            }
            Route::get('/settings/supabase-test', [SettingsController::class, 'supabaseTest'])->name('settings.supabase-test');
            Route::post('/settings/otp-test', [SettingsController::class, 'otpTest'])->name('settings.otp-test');
            Route::get('/settings/otp-balance', [SettingsController::class, 'otpBalance'])->name('settings.otp-balance');
            Route::post('/settings/email-test', [SettingsController::class, 'emailTest'])->name('settings.email-test');
            Route::get('/users', [\App\Http\Controllers\Admin\UserManagementController::class, 'index'])->name('users.index');
            Route::get('/users/create', [\App\Http\Controllers\Admin\UserManagementController::class, 'create'])->name('users.create');
            Route::post('/users', [\App\Http\Controllers\Admin\UserManagementController::class, 'store'])->name('users.store');
            Route::get('/users/{user}/view-password', [\App\Http\Controllers\Admin\UserManagementController::class, 'showPasswordForm'])->name('users.view-password-form');
            Route::post('/users/{user}/view-password', [\App\Http\Controllers\Admin\UserManagementController::class, 'viewPassword'])->name('users.view-password');
            Route::post('/users/{user}/reset-password', [\App\Http\Controllers\Admin\UserManagementController::class, 'resetPassword'])->name('users.reset-password');
            Route::post('/users/update-sms', [\App\Http\Controllers\Admin\UserManagementController::class, 'updateSms'])->name('users.update-sms');
            Route::post('/users/{user}/revoke', [\App\Http\Controllers\Admin\UserManagementController::class, 'revoke'])->name('users.revoke');
            Route::delete('/users/{user}', [\App\Http\Controllers\Admin\UserManagementController::class, 'destroy'])->name('users.destroy');
        });
    });
});

