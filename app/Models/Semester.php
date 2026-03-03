<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Semesters: 1, 2
 */
class Semester extends Model
{
    protected $table = 'semesters';

    protected $fillable = ['value', 'name', 'sort_order'];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'semester_id');
    }

    public static function ordered(): \Illuminate\Database\Eloquent\Collection
    {
        return static::orderBy('sort_order')->get();
    }
}
