<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Candidate;
use App\Models\Student;
use App\Models\Vote;
use Illuminate\Support\Facades\Cache;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $candidatesCount = Cache::remember('candidates_count', now()->addMinutes(5), function () {
            return Candidate::count();
        });

        $studentsCount = Cache::remember('students_count', now()->addMinutes(5), function () {
            return Student::count();
        });

        $totalVotes = Cache::remember('total_votes', now()->addMinutes(5), function () {
            return Vote::count();
        });

        // Get votes per college using students' 'college' field
        $votesPerCollege = Cache::remember('votes_per_college', now()->addMinutes(5), function () {
            return Student::with('votes')
                ->get()
                ->groupBy('college') // Group students by college
                ->mapWithKeys(function ($students, $college) {
                    return [$college => $students->sum(fn($student) => $student->votes->count())];
                });
        });

        // Convert votes per college into Stat cards
        $collegeStats = collect($votesPerCollege)->map(function ($votes, $collegeName) {
            return Stat::make("Votes - $collegeName", $votes);
        })->values()->all();

        return array_merge([
            Stat::make('Candidates', $candidatesCount),
            Stat::make('Student Population', $studentsCount),
            Stat::make('Total Votes', $totalVotes),
        ], $collegeStats);
    }
}
