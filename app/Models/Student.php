<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Student extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $fillable = [
        'student_id',
        'first_name',
        'last_name',
        'middle_name',  // ✅ Added new field
        'college',       // ✅ Updated from 'department' to 'college'
        'course',        // ✅ Added new field
        'session',       // ✅ Added new field
        'semester',      // ✅ Added new field
        'learning_modality', // ✅ Added new field
    ];

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
