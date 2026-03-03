<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class File extends Model
{
    protected $table = 'files';

    protected $fillable = ['file_name', 'file_path', 'file_size'];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function proposals(): HasMany
    {
        return $this->hasMany(\App\Models\DocuMentor\Proposal::class, 'file_id');
    }
}
