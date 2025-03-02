<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Election extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'start_date', 'end_date'];

    public function partylists()
    {
        return $this->hasMany(Partylist::class);
    }

    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }


    public function students()
    {
        return $this->belongsToMany(Student::class, 'election_student', 'election_id', 'student_id')->using(ElectionStudent::class);
    }

}
