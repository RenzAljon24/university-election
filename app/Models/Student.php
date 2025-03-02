<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = ['student_id', 'first_name', 'last_name', 'department'];

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }
    public function elections()
    {
        return $this->belongsToMany(Election::class, 'election_student', 'student_id', 'election_id')
            ->withTimestamps();
    }
}
