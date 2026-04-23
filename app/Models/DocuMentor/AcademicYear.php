<?php

namespace App\Models\DocuMentor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    public $timestamps = false;

    protected $table = 'academic_years';

    protected $fillable = ['year', 'is_active', 'submission_deadline', 'department_id'];

    protected $casts = [
        'is_active' => 'boolean',
        'submission_deadline' => 'date',
    ];

    /**
     * If no submission_deadline set: default = September 30 of the year following the academic year start
     * (Coordinator Flow spec). Supports year labels like "2024-2025" without throwing.
     */
    public function getEffectiveDeadlineAttribute(): \Carbon\CarbonInterface
    {
        if ($this->submission_deadline) {
            return \Carbon\Carbon::parse($this->submission_deadline)->startOfDay();
        }

        $raw = trim((string) ($this->attributes['year'] ?? ''));
        $start = null;
        if ($raw !== '' && preg_match('/^(\d{4})\s*[-\/]\s*(\d{4})\b/', $raw, $m)) {
            $start = \Carbon\Carbon::createMidnightDate((int) $m[1], 1, 1);
        } elseif ($raw !== '' && preg_match('/^(\d{4})\b/', $raw, $m)) {
            $start = \Carbon\Carbon::createMidnightDate((int) $m[1], 1, 1);
        } else {
            try {
                $start = \Carbon\Carbon::parse($raw !== '' ? $raw : (string) now()->year)->startOfDay();
            } catch (\Throwable) {
                $start = now()->startOfDay();
            }
        }

        return $start->copy()->addYear()->month(9)->day(30)->startOfDay();
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Department::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(ProjectGroup::class, 'academic_year_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'academic_year_id');
    }

    /** Academic classes in this year */
    public function academicClasses(): HasMany
    {
        return $this->hasMany(\App\Models\AcademicClass::class, 'academic_year_id');
    }

    /** Users (students) tied to this academic year */
    public function users(): HasMany
    {
        return $this->hasMany(\App\Models\User::class, 'academic_year_id');
    }

    /** Deadlines for this academic year (Department → Academic Year → Deadline) */
    public function deadlines(): HasMany
    {
        return $this->hasMany(\App\Models\Deadline::class, 'academic_year_id');
    }

    public static function active(): ?self
    {
        return static::where('is_active', true)->first();
    }
}
