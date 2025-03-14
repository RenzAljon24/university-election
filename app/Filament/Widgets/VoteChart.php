<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Vote;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class VoteChart extends ChartWidget
{
    protected static ?string $heading = 'Total Number of Voters';

    protected function getData(): array
    {
        $daysInMonth = Carbon::now()->daysInMonth;
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // Cache votes per day for 5 minutes
        $votesPerDay = Cache::remember('votes_per_day', now()->addMinutes(5), function () use ($currentMonth, $currentYear) {
            return Vote::selectRaw('DAY(created_at) as day, COUNT(id) as total')
                ->whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('total', 'day')
                ->toArray();
        });

        // Generate labels for all days of the current month
        $labels = range(1, $daysInMonth);
        $data = array_fill(1, $daysInMonth, 0); // Default all days to 0 votes

        // Fill the actual votes into the array
        foreach ($votesPerDay as $day => $totalVotes) {
            $data[$day] = $totalVotes;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Votes per Day',
                    'data' => array_values($data),
                    'borderColor' => '#3b82f6', // Blue line color
                    'backgroundColor' => 'rgba(59,130,246,0.2)', // Light blue fill
                ],
            ],
            'labels' => array_map(fn($day) => (string) $day, $labels), // Convert to string for chart
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
