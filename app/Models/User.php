<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Roles: super_admin (Admin), supervisor, coordinator, student, leader.
     * Valid roles: student, group_leader, supervisor, coordinator, admin.
     */
    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_SUPERVISOR = 'supervisor';

    protected $fillable = [
        'username',
        'email',
        'phone',
        'index_number',
        'name',
        'role',
        'password',
        'avatar',
        'institution_id',
        'sms_allocation',
        'ai_tokens_allocation',
        'faculty_id',
        'department_id',
        'group_leader',
        'coordinator',
        'role_id',
        'academic_year_id',
        'is_active',
    ];

    /**
     * Docu Mentor roles (users.role). All students are in the users table and are recognized by role.
     * Student-side roles: student, leader (group_leader capability is users.group_leader boolean).
     */
    public const DM_ROLE_STUDENT = 'student';

    public const DM_ROLE_LEADER = 'leader';

    public const DM_ROLE_SUPERVISOR = 'supervisor';

    public const DM_ROLE_HOD = 'hod';

    public const DM_ROLE_COORDINATOR = 'coordinator';

    /** Canonical role names for middleware and dashboard (aligned with roles.name). */
    public const ROLE_NAME_STUDENT = 'student';

    public const ROLE_NAME_GROUP_LEADER = 'group_leader';

    public const ROLE_NAME_SUPERVISOR = 'supervisor';

    public const ROLE_NAME_COORDINATOR = 'coordinator';

    public const ROLE_NAME_ADMIN = 'admin';

    protected $hidden = ['password', 'remember_token'];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Passwords are stored hashed (bcrypt) and never in plain text.
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'sms_allocation' => 'integer',
            'sms_used' => 'integer',
            'ai_tokens_allocation' => 'integer',
            'ai_tokens_used' => 'integer',
            'ai_tokens_reset_at' => 'datetime',
            'group_leader' => 'boolean',
            'coordinator' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function roleModel(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /** Academic year this user (student) is tied to for filtering and year-based rules. */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(\App\Models\DocuMentor\AcademicYear::class, 'academic_year_id');
    }

    /**
     * Canonical role name for access control (aligned with roles.name).
     * Prefers role_id → Role->name; falls back to mapping from legacy User->role.
     */
    public function roleName(): string
    {
        $role = $this->roleModel;
        if ($role && trim((string) $role->name) !== '') {
            $name = strtolower(trim($role->name));
            if (in_array($name, [self::ROLE_NAME_STUDENT, self::ROLE_NAME_GROUP_LEADER, self::ROLE_NAME_SUPERVISOR, self::ROLE_NAME_COORDINATOR, self::ROLE_NAME_ADMIN], true)) {
                return $name;
            }
        }
        $legacy = $this->role ?? '';
        $map = [
            self::ROLE_SUPER_ADMIN => self::ROLE_NAME_ADMIN,
            self::ROLE_SUPERVISOR => self::ROLE_NAME_SUPERVISOR,
            self::DM_ROLE_COORDINATOR => self::ROLE_NAME_COORDINATOR,
            self::DM_ROLE_STUDENT => self::ROLE_NAME_STUDENT,
            self::DM_ROLE_LEADER => self::ROLE_NAME_GROUP_LEADER,
            self::DM_ROLE_SUPERVISOR => self::ROLE_NAME_SUPERVISOR,
        ];

        return $map[$legacy] ?? self::ROLE_NAME_STUDENT;
    }

    /** Whether this user has one of the student-side roles (student or group_leader). */
    public function isStudentRole(): bool
    {
        return in_array($this->roleName(), [self::ROLE_NAME_STUDENT, self::ROLE_NAME_GROUP_LEADER], true);
    }

    /**
     * Coordinator affiliation: department from role (Coordinator → role → department).
     * Use this for filtering academic years and scoping data. Fallback to user.department_id for legacy.
     */
    public function coordinatorDepartmentId(): ?int
    {
        $id = $this->roleModel?->department_id;
        if ($id !== null) {
            return (int) $id;
        }

        return $this->department_id !== null ? (int) $this->department_id : null;
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function supervisorProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Supervisor::class);
    }

    /** SMS remaining for this supervisor (allocation minus used). */
    public function getSmsRemainingAttribute(): int
    {
        $alloc = (int) ($this->attributes['sms_allocation'] ?? 0);
        $used = (int) ($this->attributes['sms_used'] ?? 0);

        return max(0, $alloc - $used);
    }

    /** Class groups owned by this supervisor (supervisor_id on class_groups). */
    public function classGroups(): HasMany
    {
        return $this->hasMany(ClassGroup::class, 'supervisor_id');
    }

    /** Docu Mentor: Project groups this user belongs to */
    public function docuMentorGroups(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\DocuMentor\ProjectGroup::class,
            'group_members',
            'user_id',
            'group_id'
        );
    }

    /**
     * After a row in students (profile / OTP) is saved, copy a proper display name and phone
     * onto all Docu Mentor users with the same index so supervisor/coordinator lists use users.* only.
     */
    public static function syncDocuMentorUserFromStudentProfile(?Student $student): void
    {
        if (! $student || trim((string) ($student->index_number ?? '')) === '') {
            return;
        }

        $norm = Student::normalizeIndex($student->index_number);
        $users = self::query()
            ->whereIn('role', [self::DM_ROLE_STUDENT, self::DM_ROLE_LEADER])
            ->whereRaw('LOWER(TRIM(COALESCE(index_number, ""))) = ?', [$norm])
            ->get();

        $sn = trim((string) ($student->student_name ?? ''));
        $pc = $student->phone_contact ? Student::normalizePhoneForStorage($student->phone_contact) : null;

        foreach ($users as $u) {
            if ($sn !== '' && ! self::docuMentorNameIsIndexNumber($sn, (string) $u->index_number, (string) $u->username)) {
                $u->name = $sn;
            }
            if ($pc !== null && $pc !== '') {
                $u->phone = $pc;
            }
            $u->save();
        }
    }

    /**
     * Push a non-index display name from the students row to Docu Mentor users and class rosters.
     */
    public static function propagateDocuMentorDisplayNameFromStudent(?Student $student): void
    {
        if (! $student || trim((string) ($student->index_number ?? '')) === '') {
            return;
        }

        self::syncDocuMentorUserFromStudentProfile($student);

        $sn = trim((string) ($student->student_name ?? ''));
        $idx = trim((string) ($student->index_number ?? ''));
        if ($sn === '' || self::docuMentorNameIsIndexNumber($sn, $idx, null)) {
            return;
        }

        if (Schema::hasTable('class_group_students')) {
            ClassGroupStudent::query()
                ->whereRaw('LOWER(TRIM(COALESCE(class_group_students.index_number, ""))) = ?', [Student::normalizeIndex($student->index_number)])
                ->update(['student_name' => $sn]);
        }
    }

    /**
     * When a student/leader updates users.name only (no students row), mirror to students + class rosters when possible.
     */
    public static function propagateDocuMentorDisplayNameFromUser(self $actor): void
    {
        if (! $actor->isDocuMentorStudent()) {
            return;
        }

        $idx = trim((string) ($actor->index_number ?? ''));
        if ($idx === '') {
            return;
        }

        $nm = trim((string) ($actor->name ?? ''));
        if ($nm === '' || self::docuMentorNameIsIndexNumber($nm, $idx, (string) $actor->username)) {
            return;
        }

        if (Schema::hasTable('students')) {
            $st = Student::query()->where('index_number_hash', Student::hashIndexNumber($idx))->first();
            if ($st) {
                $st->student_name = $nm;
                $st->save();
            }
        }

        if (Schema::hasTable('class_group_students')) {
            ClassGroupStudent::query()
                ->whereRaw('LOWER(TRIM(COALESCE(class_group_students.index_number, ""))) = ?', [Student::normalizeIndex($idx)])
                ->update(['student_name' => $nm]);
        }
    }

    /**
     * Batch-load data for Docu Mentor member lists: class roster names (optional) and
     * {@see Student} rows for OTP/profile phone numbers (same index hash as login).
     */
    public static function eagerLoadDocuMentorMemberProfiles(Collection $users): void
    {
        if ($users->isEmpty()) {
            return;
        }

        if (Schema::hasTable('class_group_students')) {
            $norms = $users->map(fn ($u) => Student::normalizeIndex($u->index_number ?? ''))->filter()->unique()->values()->all();
            if ($norms !== []) {
                $placeholders = implode(',', array_fill(0, count($norms), '?'));
                $rows = ClassGroupStudent::query()
                    ->whereRaw('LOWER(TRIM(COALESCE(class_group_students.index_number, ""))) in ('.$placeholders.')', $norms)
                    ->orderBy('id')
                    ->get();
                $byNorm = [];
                foreach ($rows as $row) {
                    $n = Student::normalizeIndex($row->index_number);
                    if (! isset($byNorm[$n])) {
                        $byNorm[$n] = $row;
                    }
                }
                foreach ($users as $u) {
                    $n = Student::normalizeIndex($u->index_number ?? '');
                    $u->setRelation('docuMentorClassGroupStudent', $byNorm[$n] ?? null);
                }
            } else {
                foreach ($users as $u) {
                    $u->setRelation('docuMentorClassGroupStudent', null);
                }
            }
        } else {
            foreach ($users as $u) {
                $u->setRelation('docuMentorClassGroupStudent', null);
            }
        }

        if (! Schema::hasTable('students')) {
            foreach ($users as $u) {
                $u->setRelation('docuMentorPhoneStudent', null);
            }

            return;
        }

        $hashes = [];
        foreach ($users as $u) {
            $i = trim((string) ($u->index_number ?? ''));
            if ($i !== '') {
                $hashes[] = Student::hashIndexNumber(Student::normalizeIndex($i));
            }
        }
        $hashes = array_values(array_unique(array_filter($hashes)));
        if ($hashes === []) {
            foreach ($users as $u) {
                $u->setRelation('docuMentorPhoneStudent', null);
            }

            return;
        }

        $byHash = Student::query()->whereIn('index_number_hash', $hashes)->get()->keyBy('index_number_hash');

        foreach ($users as $u) {
            $i = trim((string) ($u->index_number ?? ''));
            if ($i === '') {
                $u->setRelation('docuMentorPhoneStudent', null);

                continue;
            }
            $h = Student::hashIndexNumber(Student::normalizeIndex($i));
            $u->setRelation('docuMentorPhoneStudent', $byHash->get($h));
        }
    }

    /**
     * True when $name is the same identifier as the student's index (any common casing/spacing variant).
     */
    public static function docuMentorNameIsIndexNumber(?string $name, ?string $indexNumber, ?string $username = null): bool
    {
        $nm = trim((string) $name);
        $idx = trim((string) $indexNumber);
        $uname = trim((string) $username);
        if ($nm === '') {
            return false;
        }
        if ($idx !== '' && strcasecmp($nm, $idx) === 0) {
            return true;
        }
        if ($idx !== '' && Student::normalizeIndex($nm) === Student::normalizeIndex($idx) && Student::normalizeIndex($idx) !== '') {
            return true;
        }
        if ($idx !== '' && self::docuMentorMemberIdentitySignature($nm) === self::docuMentorMemberIdentitySignature($idx)
            && self::docuMentorMemberIdentitySignature($idx) !== '') {
            return true;
        }
        if ($uname !== '' && strcasecmp($nm, $uname) === 0) {
            return true;
        }

        return false;
    }

    /**
     * Letters/digits only, uppercased — for comparing index-like strings across punctuation/case.
     */
    public static function docuMentorMemberIdentitySignature(?string $value): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', (string) $value));
    }

    /**
     * Human-readable name for Docu Mentor member lists (supervisor/coordinator).
     * Uses the linked user account (synced from student profile) and class roster — not the students table.
     */
    public function docuMentorMemberDisplayName(): string
    {
        $idx = trim((string) ($this->index_number ?? ''));
        $uname = trim((string) ($this->username ?? ''));
        $nm = trim((string) ($this->name ?? ''));

        if ($nm !== '' && ! self::docuMentorNameIsIndexNumber($nm, $idx, $uname)) {
            return $nm;
        }

        $cg = $this->relationLoaded('docuMentorClassGroupStudent') ? $this->getRelation('docuMentorClassGroupStudent') : null;
        if ($cg instanceof ClassGroupStudent) {
            $sn = trim((string) ($cg->student_name ?? ''));
            if ($sn !== '' && ! self::docuMentorNameIsIndexNumber($sn, $idx, $uname)) {
                return $sn;
            }
        }

        return '—';
    }

    /**
     * Group members coordinators/supervisors may list on assigned-project views.
     * Omits index-only placeholders (same rule as {@see self::docuMentorMemberDisplayName()} returning "—").
     *
     * @param  iterable<int, self>  $members
     * @return Collection<int, self>
     */
    public static function docuMentorGroupMembersVisibleToStaff(iterable $members): Collection
    {
        return collect($members)
            ->filter(fn ($u) => $u instanceof self && $u->docuMentorMemberDisplayName() !== '—')
            ->values();
    }

    /**
     * Phone for member lists: prefer users.phone; then OTP account phone_contact on students (same index hash).
     * Many logins use students.phone_contact while users.phone stays empty or pending_* until sync runs.
     */
    public function docuMentorMemberDisplayPhone(): string
    {
        $p = trim((string) ($this->attributes['phone'] ?? ''));
        if ($p !== '' && ! str_starts_with($p, 'pending_')) {
            return $p;
        }

        $stu = $this->relationLoaded('docuMentorPhoneStudent') ? $this->getRelation('docuMentorPhoneStudent') : null;
        if ($stu instanceof Student) {
            $raw = trim((string) ($stu->phone_contact ?? ''));
            if ($raw !== '') {
                return Student::normalizePhoneForStorage($raw) ?? $raw;
            }
        }

        return 'No phone';
    }

    /**
     * Best-effort phone for SMS (users.phone or students.phone_contact for same index).
     * Returns digits-only string suitable for Arkesel, or null.
     */
    public function docuMentorSmsPhone(): ?string
    {
        $raw = trim((string) ($this->attributes['phone'] ?? ''));
        if ($raw !== '' && ! str_starts_with($raw, 'pending_')) {
            $digits = preg_replace('/\D/', '', $raw) ?? '';

            return strlen($digits) >= 10 ? $digits : null;
        }

        if (! $this->relationLoaded('docuMentorPhoneStudent') && $this->isDocuMentorStudent() && Schema::hasTable('students')) {
            $idx = trim((string) ($this->index_number ?? ''));
            if ($idx !== '') {
                $stu = Student::query()->where('index_number_hash', Student::hashIndexNumber($idx))->first();
                $this->setRelation('docuMentorPhoneStudent', $stu);
            }
        }

        $stu = $this->relationLoaded('docuMentorPhoneStudent') ? $this->getRelation('docuMentorPhoneStudent') : null;
        if ($stu instanceof Student) {
            $norm = $stu->phone_contact ? Student::normalizePhoneForStorage($stu->phone_contact) : null;
            if ($norm) {
                $digits = preg_replace('/\D/', '', $norm) ?? '';

                return strlen($digits) >= 10 ? $digits : null;
            }
        }

        return null;
    }

    /** Docu Mentor: Groups where this user is leader */
    public function ledDocuMentorGroups(): HasMany
    {
        return $this->hasMany(\App\Models\DocuMentor\ProjectGroup::class, 'leader_id');
    }

    public function isDocuMentorStudent(): bool
    {
        return in_array($this->role, [self::DM_ROLE_STUDENT, self::DM_ROLE_LEADER], true);
    }

    /** Docu Mentor supervisor (reviews projects). Uses role column and role_id → roles.name. */
    public function isDocuMentorSupervisor(): bool
    {
        if (in_array($this->role, [self::ROLE_SUPERVISOR, self::DM_ROLE_SUPERVISOR], true)) {
            return true;
        }

        return $this->roleName() === self::ROLE_NAME_SUPERVISOR;
    }

    /** Docu Mentor: Projects this user supervises */
    public function supervisedProjects(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\DocuMentor\Project::class,
            'project_supervisors',
            'user_id',
            'project_id'
        );
    }

    /** Access to coordinator dashboard: role or coordinator flag. */
    public function isDocuMentorCoordinator(): bool
    {
        return $this->role === self::DM_ROLE_COORDINATOR
            || (bool) ($this->attributes['coordinator'] ?? false)
            || $this->isSuperAdmin();
    }

    /** Can create/manage groups (add first member = auto-create group). Set by coordinator. */
    public function isGroupLeader(): bool
    {
        return (bool) ($this->attributes['group_leader'] ?? false);
    }

    /**
     * Group leaders can create/start Docu Mentor projects. No level-based eligibility.
     */
    public function canLeadDocuMentorProjects(): bool
    {
        return $this->isGroupLeader();
    }

    /**
     * Student can access project area if they are student/group_leader and either:
     * - are a group leader, or
     * - belong to a Docu Mentor group, or
     * - lead a Docu Mentor group.
     * Access is group-driven only; no level or course checks.
     */
    public function canAccessDocuMentorProjects(): bool
    {
        if (! $this->isDocuMentorStudent()) {
            return false;
        }

        return $this->canLeadDocuMentorProjects()
            || $this->docuMentorGroups()->exists()
            || $this->ledDocuMentorGroups()->exists();
    }

    /**
     * Whether user is a group leader (for Documentor project submission only). No level/class-rep logic.
     */
    public function isClassRep(): bool
    {
        return (bool) ($this->attributes['group_leader'] ?? false);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isStaff(): bool
    {
        return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_SUPERVISOR], true);
    }

    /**
     * Coordinator (or Super Admin) with SMS balance who "has" this class group.
     * Used for OTP/SMS deduction. Coordinator scope by department matches the class group supervisor's department.
     */
    public static function coordinatorWithSmsBalanceForClassGroup(ClassGroup $classGroup): ?self
    {
        $classGroup->load('supervisor');
        $supervisorDepartmentId = $classGroup->supervisor?->department_id;

        $q = self::query()
            ->where(function ($q) {
                $q->where('role', self::DM_ROLE_COORDINATOR)
                    ->orWhere('role', self::ROLE_SUPER_ADMIN)
                    ->orWhere('coordinator', true);
            })
            ->whereRaw('(COALESCE(sms_allocation, 0) - COALESCE(sms_used, 0)) > 0');

        $q->where(function ($q) use ($supervisorDepartmentId) {
            $q->whereNull('department_id');
            if ($supervisorDepartmentId !== null) {
                $q->orWhere('department_id', $supervisorDepartmentId);
            }
        });

        return $q->first();
    }

    /** IDs of class groups in scope. System uses users-only (no class_groups table); always returns empty. */
    public function classGroupIds(): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('class_groups')) {
            return [];
        }
        if ($this->isSuperAdmin()) {
            return ClassGroup::pluck('id')->all();
        }
        if ($this->isDocuMentorCoordinator()) {
            $q = ClassGroup::query();
            if ($this->department_id) {
                $q->whereHas('supervisor', fn ($s) => $s->where('department_id', $this->department_id));
            }

            return $q->pluck('id')->all();
        }

        return $this->classGroups()->pluck('id')->all();
    }

    /** Docu Mentor students in scope: coordinator's department (from role → department). */
    public function docuMentorStudentsInScope(): \Illuminate\Database\Eloquent\Builder
    {
        $q = User::whereIn('role', [self::DM_ROLE_STUDENT, self::DM_ROLE_LEADER])->orderBy('name');
        $deptId = $this->coordinatorDepartmentId();
        if ($deptId !== null) {
            $q->where('department_id', $deptId);
        }

        return $q;
    }

    /** Supervisors visible to this coordinator: same department (from role → department). Super Admin or no department sees all. */
    public function supervisorsInScope(): \Illuminate\Database\Eloquent\Builder
    {
        $q = User::where('role', self::ROLE_SUPERVISOR)->orderBy('name');
        if ($this->isSuperAdmin()) {
            return $q;
        }
        $deptId = $this->coordinatorDepartmentId();
        if ($deptId === null) {
            return $q;
        }

        return $q->where('department_id', $deptId);
    }

    /** Full URL for avatar (Cloudinary URL or local storage path). */
    public function getAvatarUrlAttribute(): ?string
    {
        if (empty($this->avatar)) {
            return null;
        }
        if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
            return $this->avatar;
        }

        return asset('storage/'.$this->avatar);
    }

    /**
     * Find or create a Docu Mentor User for a Student (index+phone account).
     * Used when adding a member by phone and the phone is in students.phone_contact but not users.phone.
     */
    public static function findOrCreateDocuMentorUserForStudent(Student $student): ?User
    {
        $indexNormalized = trim($student->index_number ?? '');
        $phone = $student->phone_contact ? preg_replace('/\D/', '', (string) $student->phone_contact) : null;

        $user = null;
        if ($phone && $phone !== '') {
            $user = self::where('phone', $phone)
                ->orWhere('phone', 'like', $phone.'%')
                ->whereIn('role', [self::DM_ROLE_STUDENT, self::DM_ROLE_LEADER])
                ->first();
        }
        if (! $user && $indexNormalized !== '') {
            $user = self::where('index_number', $indexNormalized)
                ->whereIn('role', [self::DM_ROLE_STUDENT, self::DM_ROLE_LEADER])
                ->first();
        }

        if ($user) {
            return $user;
        }

        if (! $phone || $phone === '') {
            return null;
        }

        $username = 'idx_'.(preg_replace('/[^a-zA-Z0-9]/', '', $indexNormalized) ?: $phone);
        if (self::where('username', $username)->exists()) {
            $username = $username.'_'.substr(md5($indexNormalized.$phone), 0, 6);
        }

        return self::create([
            'username' => $username,
            'index_number' => $indexNormalized ?: null,
            'phone' => $phone,
            'name' => $student->student_name ?? $student->index_number ?? $username,
            'role' => self::DM_ROLE_STUDENT,
            'password' => Hash::make(bin2hex(random_bytes(16))),
        ]);
    }

    /**
     * Create a Docu Mentor User for a Student when findOrCreateDocuMentorUserForStudent returned null (e.g. no phone yet).
     * Ensures OTP login can always log in the student to the dashboard.
     */
    public static function createDocuMentorUserForStudent(Student $student): User
    {
        $indexNormalized = trim((string) ($student->index_number ?? ''));
        $phone = $student->phone_contact ? preg_replace('/\D/', '', (string) $student->phone_contact) : null;
        $base = preg_replace('/[^a-zA-Z0-9]/', '', $indexNormalized) ?: 'idx';
        $username = 'idx_'.$base;
        if (self::where('username', $username)->exists()) {
            $username = $username.'_'.substr(md5($indexNormalized.($phone ?? '').$student->id), 0, 6);
        }

        return self::create([
            'username' => $username,
            'index_number' => $indexNormalized ?: null,
            'phone' => $phone ?? ('pending_'.$student->id),
            'name' => $student->student_name ?? $student->index_number ?? $username,
            'role' => self::DM_ROLE_STUDENT,
            'password' => Hash::make(bin2hex(random_bytes(16))),
        ]);
    }
}
