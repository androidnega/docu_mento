<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ValidIndex extends Model
{
    protected $table = 'valid_indices';

    protected $fillable = ['index_number', 'student_name'];
}
