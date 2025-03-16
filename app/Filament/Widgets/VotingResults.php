<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Election;

class VotingResults extends Widget
{
    protected static string $view = 'filament.widgets.voting-results';
    protected int|array|string $columnSpan = 'full'; // ✅ Correct type

    public $elections;

    public function mount()
    {
        // ✅ Fetch elections with candidates, partylists, and votes
        $this->elections = Election::with(['candidates.partylist', 'candidates.votes'])
            ->get()
            ->map(function ($election) {
                return [
                    'name' => $election->name,
                    'candidates' => $election->candidates->map(function ($candidate) {
                        return [
                            'name' => $candidate->name,
                            'position' => $candidate->position,
                            'partylist' => $candidate->partylist?->name ?? 'Independent',
                            'votes' => $candidate->votes->count(),
                        ];
                    })->groupBy('position'), // ❌ Removed sortByDesc('votes')
                ];
            });
    }
}
