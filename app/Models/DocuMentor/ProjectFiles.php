<?php

namespace App\Models\DocuMentor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class ProjectFiles extends Model
{
    protected $table = 'project_files';

    /** True when the backing table exists (migrations may not have run on some hosts). */
    public static function tableExists(): bool
    {
        return Schema::hasTable((new static)->getTable());
    }

    protected $fillable = [
        'brief_pdf', 'diary_pdf', 'assessment_file', 'assessment_form_file',
        'uploaded_at', 'project_id',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
