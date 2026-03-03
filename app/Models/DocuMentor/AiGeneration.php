<?php

namespace App\Models\DocuMentor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiGeneration extends Model
{
    protected $table = 'ai_generations';

    protected $fillable = ['submission_id', 'ai_output_json'];

    protected $casts = [
        'ai_output_json' => 'array',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
