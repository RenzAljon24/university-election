<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Student;
use App\Models\Partylist;
use App\Models\Vote;
use App\Models\Candidate;



Route::post('/login', function (Request $request) {
    $request->validate([
        'student_id' => 'required',
        'password' => 'required',
    ]);

    $student = Student::where('student_id', $request->student_id)->first();

    if (!$student) {
        return response()->json(['message' => 'Invalid student ID'], 401);
    }

    // Normalize and remove special characters
    function sanitizeName($name)
    {
        // Convert to ASCII (removes accents like é -> e)
        $name = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
        // Remove non-alphabetic characters
        return preg_replace('/[^a-zA-Z]/', '', $name);
    }

    $first_name = sanitizeName($student->first_name);
    $last_name = sanitizeName($student->last_name);

    // Generate expected password format
    $expectedPassword = strtolower(Str::substr($first_name, 0, 2) . Str::substr($last_name, 0, 2) . $request->student_id);

    if ($request->password !== $expectedPassword) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    return response()->json([
        'message' => 'Login successful',
        'student' => [
            'id' => $student->id,
            'student_id' => $student->student_id,
            'name' => $student->name
        ]
    ]);
});
Route::get('/student/{id}/elections', function ($id) {
    $student = Student::with('elections.partyLists')->find($id);

    if (!$student) {
        return response()->json(['message' => 'Student not found'], 404);
    }

    return response()->json([
        'student' => $student->name,
        'elections' => $student->elections->map(function ($election) {
            return [
                'id' => $election->id,
                'name' => $election->name,
                'partyLists' => $election->partyLists->map(function ($party) {
                    return [
                        'id' => $party->id,
                        'name' => $party->name
                    ];
                })
            ];
        })
    ]);
});

Route::get('/partylist/{id}/candidates', function ($id) {
    $partylist = Partylist::with('candidates')->find($id);

    if (!$partylist) {
        return response()->json(['message' => 'Partylist not found'], 404);
    }

    return response()->json([
        'partylist' => $partylist->name,
        'candidates' => $partylist->candidates->map(function ($candidate) {
            return [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'position' => $candidate->position,
                'photo' => asset('storage/' . $candidate->photo)
            ];
        })
    ]);
});

Route::post('/vote', function (Request $request) {
    try {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'election_id' => 'required|exists:elections,id',
            'votes' => 'required|array',
            'votes.*.candidate_id' => 'nullable|exists:candidates,id', // Allow null for abstain
            'votes.*.position' => 'required|string',
        ]);

        foreach ($request->votes as $voteData) {
            $alreadyVoted = DB::table('votes')
                ->where('student_id', $request->student_id)
                ->where('election_id', $request->election_id)
                ->where('position', $voteData['position'])
                ->exists();

            if ($alreadyVoted) {
                return response()->json(['message' => 'You have already voted or abstained for this position'], 400);
            }

            Vote::create([
                'student_id' => $request->student_id,
                'election_id' => $request->election_id,
                'candidate_id' => $voteData['candidate_id'] ?? null, // Null for abstain
                'position' => $voteData['position'],
            ]);
        }

        return response()->json(['message' => 'All votes cast successfully!'], 200);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['message' => $e->errors()], 422);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Server error: ' . $e->getMessage()], 500);
    }
});
Route::post('/logout', function (Request $request) {
    return response()->json(['message' => 'Logged out successfully']);
});
Route::get('/student/{id}/elections-with-candidates', function ($id) {
    $student = Student::with('elections.partylists.candidates')->find($id);

    if (!$student) {
        return response()->json(['message' => 'Student not found'], 404);
    }

    $data = $student->elections->map(function ($election) {
        return [
            'election_id' => $election->id,
            'election_name' => $election->name,
            'partylists' => $election->partylists->map(function ($partylist) {
                return [
                    'partylist_id' => $partylist->id,
                    'partylist_name' => $partylist->name,
                    'candidates' => $partylist->candidates->groupBy('position')->map(function ($candidates) {
                        return $candidates->map(function ($candidate) {
                            return [
                                'id' => $candidate->id,
                                'name' => $candidate->name,
                                'photo' => $candidate->photo ? URL::to('/storage/' . $candidate->photo) : null
                            ];
                        });
                    })
                ];
            })
        ];
    });

    return response()->json($data);
});



Route::get('/student/{id}/voting-status', function ($id) {
    $alreadyVoted = DB::table('votes')->where('student_id', $id)->exists();

    return response()->json([
        'already_voted' => $alreadyVoted
    ]);
});
