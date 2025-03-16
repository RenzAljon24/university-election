<?php

namespace App\Http\Controllers;

use App\Models\Election;
use Illuminate\Http\JsonResponse;

class ElectionController extends Controller
{
    public function getFirstElectionResults(): JsonResponse
    {
        $election = Election::with(['candidates.partylist', 'candidates.votes'])
            ->first(); // Fetch only the first election

        if (!$election) {
            return response()->json(['message' => 'No election found'], 404);
        }

        $formattedElection = [
            'name' => $election->name,
            'candidates' => $election->candidates->map(function ($candidate) {
                return [
                    'name' => $candidate->name,
                    'position' => $candidate->position,
                    'partylist' => $candidate->partylist?->name ?? 'Independent',
                    'photo' => asset('storage/' . $candidate->photo),
                    'votes' => $candidate->votes->count(),
                ];
            })->groupBy('position'), // Group candidates by position
        ];

        return response()->json($formattedElection);
    }
}
