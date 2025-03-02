<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ElectionStudent extends Pivot
{
    use HasFactory;

    protected $table = 'election_student';

    protected $fillable = ['election_id', 'student_id', 'voted_at'];

    protected $casts = [
        'voted_at' => 'datetime',
    ];
}
