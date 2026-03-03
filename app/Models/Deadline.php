<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deadline extends Model
{
    protected $fillable = ['department_id', 'academic_year_id', 'deadline_date', 'description'];

    protected $casts = [
        'deadline_date' => 'date',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** Academic year this deadline applies to (Department → Academic Year → Deadline). */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(\App\Models\DocuMentor\AcademicYear::class, 'academic_year_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(\App\Models\DocuMentor\Project::class, 'deadline_id');
    }
}
