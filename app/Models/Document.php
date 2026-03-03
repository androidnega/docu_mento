<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Core Docu Mento document model.
 * Table: documents (future-proof; current schema may evolve).
 */
class Document extends Model
{
    protected $table = 'documents';

    protected $guarded = [];
}

