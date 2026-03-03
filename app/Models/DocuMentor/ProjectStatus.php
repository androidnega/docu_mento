<?php

namespace App\Models\DocuMentor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectStatus extends Model
{
    public $timestamps = false;

    protected $table = 'project_statuses';

    protected $fillable = ['name'];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'status_id');
    }
}
