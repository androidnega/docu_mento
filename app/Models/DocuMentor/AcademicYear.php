<?php

namespace App\Models\DocuMentor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicYear extends Model
{
    public $timestamps = false;

    protected $table = 'academic_years';

    protected $fillable = ['year', 'is_active', 'submission_deadline', 'department_id'];

    protected $casts = [
        'is_active' => 'boolean',
        'submission_deadline' => 'date',
    ];

    /** If no submission_deadline set: default = September 30 of that academic year (Coordinator Flow spec). */
    public function getEffectiveDeadlineAttribute(): \Carbon\Carbon
    {
        return $this->submission_deadline ?? \Carbon\Carbon::parse($this->year)->addYear()->setMonth(9)->setDay(30);
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
