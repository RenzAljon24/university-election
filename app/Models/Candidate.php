<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = ['election_id', 'partylist_id', 'name', 'position', 'photo', 'created_at'];

    public function election()
    {
        return $this->belongsTo(Election::class);
    }

    public function partylist()
    {
        return $this->belongsTo(Partylist::class);
    }

    public function votes()
    {
        return $this->hasMany(Vote::class, 'candidate_id');
    }
}
