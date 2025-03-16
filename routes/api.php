<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Student;
use App\Models\Partylist;
use App\Models\Vote;
use App\Models\Candidate;

use App\Http\Controllers\ElectionController;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;



Route::post('/login', function (Request $request) {
    $request->validate([
        'student_id' => 'required|string',
        'password' => 'required|string',
    ]);

    if (RateLimiter::tooManyAttempts('login:' . $request->student_id, 5)) {
        return response()->json(['message' => 'Too many attempts. Try again later.'], 429);
    }

    // Use cache to reduce database hits
    $student = Cache::remember("student_{$request->student_id}", 600, function () use ($request) {
        return Student::where('student_id', $request->student_id)->first();
    });

    if (!$student) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    // Generate expected password format
    $expectedPassword = strtolower(substr($student->first_name, 0, 2) . substr($student->last_name, 0, 2) . $student->student_id);

    // Check if password matches
    if ($request->password !== $expectedPassword) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    RateLimiter::clear('login:' . $request->student_id);

    // Reuse existing token if available
    $token = $student->tokens()->latest()->first()?->token ?? $student->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'Login successful',
        'token' => $token,
        'student' => $student
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
    $request->user()->currentAccessToken()->delete(); // Revoke the token
    return response()->json(['message' => 'Logged out successfully']);
})->middleware('auth:sanctum');

Route::get('/student/{id}/elections-with-candidates', function ($id) {
    $student = Student::with([
        'elections.partylists.candidates' => function ($query) {
            $query->orderBy('created_at', 'asc'); // Pre-order candidates by creation time
        }
    ])->find($id);

    if (!$student) {
        return response()->json(['message' => 'Student not found'], 404);
    }

    $data = $student->elections->map(function ($election) {
        // Collect all candidates into a position-based structure
        $positionCandidates = [];

        foreach ($election->partylists as $partylist) {
            foreach ($partylist->candidates as $candidate) {
                $position = $candidate->position;

                if (!isset($positionCandidates[$position])) {
                    $positionCandidates[$position] = [];
                }

                $positionCandidates[$position][] = [
                    'id' => $candidate->id,
                    'name' => $candidate->name,
                    'photo' => $candidate->photo ? URL::to('/storage/' . $candidate->photo) : null,
                    'partylist_name' => $partylist->name,
                    'created_at' => $candidate->created_at->toISOString(), // Include for ordering
                ];
            }
        }

        // Sort positions by the earliest created_at
        uasort($positionCandidates, function ($a, $b) {
            $minA = min(array_column($a, 'created_at'));
            $minB = min(array_column($b, 'created_at'));
            return $minA <=> $minB;
        });

        // Remove created_at from the final output (optional; keep if frontend needs it)
        $positions = array_map(function ($candidates) {
            return array_map(function ($candidate) {
                unset($candidate['created_at']); // Remove if not needed in frontend
                return $candidate;
            }, $candidates);
        }, $positionCandidates);

        return [
            'election_id' => $election->id,
            'election_name' => $election->name,
            'positions' => $positions,
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


Route::get('/election-results', [ElectionController::class, 'getFirstElectionResults']);