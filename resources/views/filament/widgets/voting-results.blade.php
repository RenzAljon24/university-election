<x-filament::widget class="mt-10">
    @foreach ($this->elections as $election)

        <h3 class="text-lg font-semibold">{{ $election['name'] }}</h3>

        <table class="w-full border mt-2">
            <thead>
                <tr class="bg-gray-500">
                    <th class="p-2 text-left">#</th>
                    <th class="p-2 text-left">Partylist</th>
                    <th class="p-2 text-left">Candidate</th>
                    <th class="p-2 text-left">Position</th>
                    <th class="p-2 text-left">Votes</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($election['candidates'] as $position => $candidates)
                    @foreach ($candidates as $rank => $candidate)
                        <tr class="@if($rank === 0) bg-green-100 @endif">
                            <td class="p-2">{{ $rank + 1 }}</td>
                            <td class="p-2">{{ $candidate['partylist'] }}</td>
                            <td class="p-2">{{ $candidate['name'] }}</td>
                            <td class="p-2">{{ $candidate['position'] }}</td>
                            <td class="p-2">{{ $candidate['votes'] }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>

    @endforeach
</x-filament::widget>