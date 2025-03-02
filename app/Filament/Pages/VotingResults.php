<?php



namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Election;

class VotingResults extends Page
{
    protected static string $view = 'filament.pages.voting-results';
    protected static ?string $navigationGroup = 'Election';

    public $elections; // Make elections accessible in Blade

    public function mount()
    {
        $this->elections = Election::with(['candidates.partylist', 'candidates.votes'])
            ->get()
            ->map(function ($election) {
                return [
                    'name' => $election->name,
                    'positions' => $election->candidates->groupBy('position')->map(function ($candidates) {
                        return $candidates->sortByDesc(fn($c) => $c->votes->count());
                    }),
                ];
            });
    }

    protected function getViewData(): array
    {
        return [
            'elections' => $this->elections,
        ];
    }
}
