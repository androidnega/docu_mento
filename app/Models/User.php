<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

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

    /** Docu Mentor: Groups where this user is leader */
    public function ledDocuMentorGroups(): HasMany
    {
        return $this->hasMany(\App\Models\DocuMentor\ProjectGroup::class, 'leader_id');
    }

    public function isDocuMentorStudent(): bool
    {
        return in_array($this->role, [self::DM_ROLE_STUDENT, self::DM_ROLE_LEADER], true);
    }

    /** Docu Mentor supervisor (reviews projects). */
    public function isDocuMentorSupervisor(): bool
    {
        return $this->role === self::ROLE_SUPERVISOR;
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
        if (!$this->isDocuMentorStudent()) {
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
        if (!\Illuminate\Support\Facades\Schema::hasTable('class_groups')) {
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
        return asset('storage/' . $this->avatar);
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
                ->orWhere('phone', 'like', $phone . '%')
                ->whereIn('role', [self::DM_ROLE_STUDENT, self::DM_ROLE_LEADER])
                ->first();
        }
        if (!$user && $indexNormalized !== '') {
            $user = self::where('index_number', $indexNormalized)
                ->whereIn('role', [self::DM_ROLE_STUDENT, self::DM_ROLE_LEADER])
                ->first();
        }

        if ($user) {
            return $user;
        }

        if (!$phone || $phone === '') {
            return null;
        }

        $username = 'idx_' . (preg_replace('/[^a-zA-Z0-9]/', '', $indexNormalized) ?: $phone);
        if (self::where('username', $username)->exists()) {
            $username = $username . '_' . substr(md5($indexNormalized . $phone), 0, 6);
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
        $username = 'idx_' . $base;
        if (self::where('username', $username)->exists()) {
            $username = $username . '_' . substr(md5($indexNormalized . ($phone ?? '') . $student->id), 0, 6);
        }
        return self::create([
            'username' => $username,
            'index_number' => $indexNormalized ?: null,
            'phone' => $phone ?? ('pending_' . $student->id),
            'name' => $student->student_name ?? $student->index_number ?? $username,
            'role' => self::DM_ROLE_STUDENT,
            'password' => Hash::make(bin2hex(random_bytes(16))),
        ]);
    }
}
