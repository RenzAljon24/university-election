<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    use HasFactory;

    protected $fillable = ['student_id', 'position', 'election_id', 'candidate_id', 'voted_at'];

    protected $casts = [
        'voted_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class, 'candidate_id');
    }
    public function election()
    {
        return $this->belongsTo(Election::class, 'election_id');
    }
}
