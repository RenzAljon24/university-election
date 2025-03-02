<x-filament::page>
    <div class="p-6">
        <h1 class="text-2xl font-bold mb-6">Voting Results</h1>

        @foreach ($elections as $election)
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4 text-blue-600">{{ $election['name'] }}</h2>

                <table class="w-full border-collapse border border-gray-300">
                    <thead>
                        <tr class="bg-gray-200">
                            <th class="border border-gray-300 p-2">Position</th>
                            <th class="border border-gray-300 p-2">Candidate</th>
                            <th class="border border-gray-300 p-2">Partylist</th>
                            <th class="border border-gray-300 p-2">Votes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($election['positions'] as $position => $candidates)
                            @foreach ($candidates as $index => $candidate)
                                <tr>
                                    @if ($index === 0)
                                        <td class="border border-gray-300 p-2 font-bold text-center" rowspan="{{ count($candidates) }}">
                                            {{ $position }}
                                        </td>
                                    @endif
                                    <td class="border border-gray-300 p-2">{{ $candidate->name }}</td>
                                    <td class="border border-gray-300 p-2">{{ $candidate->partylist->name ?? 'Independent' }}</td>
                                    <td class="border border-gray-300 p-2 text-center">{{ $candidate->votes->count() }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>
</x-filament::page>