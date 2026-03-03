<?php

namespace App\Models\DocuMentor;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\DocuMentor\DocumentAiReview;

/**
 * Docu Mentor chapter submission. Table: submissions (id, chapter_id, uploaded_by_id, file, file_id, comment, is_open, …).
 * uploadedBy → User. Phase 5: file_id, comments(), aiGenerations(); optional soft deletes.
 */
class Submission extends Model
{
    use SoftDeletes;

    protected $table = 'submissions';

    /**
     * Legacy table may not have created_at/updated_at; set to true if your table has them.
     */
    public $timestamps = false;

    protected $fillable = [
        'file', 'file_id', 'comment', 'score', 'submitted_at', 'is_open',
        'chapter_id', 'uploaded_by_id',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'is_open' => 'boolean',
        'score' => 'integer',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    public function fileRef(): BelongsTo
    {
        return $this->belongsTo(\App\Models\File::class, 'file_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function aiGenerations(): HasMany
    {
        return $this->hasMany(AiGeneration::class);
    }

    /** AI reviews for this submission (max 2 per submission). */
    public function aiReviews(): HasMany
    {
        return $this->hasMany(DocumentAiReview::class);
    }
}
