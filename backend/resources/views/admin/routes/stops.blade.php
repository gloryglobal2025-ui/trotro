@extends('admin.layout')

@section('content')
@php
    $rows = old('stops');
    if ($rows === null) {
        $rows = $route->stops->map(fn ($stop) => [
            'bus_stop_id' => $stop->bus_stop_id,
            'fare_from_origin' => $stop->fare_from_origin,
        ])->all();

        if (count($rows) === 0) {
            $rows = [
                ['bus_stop_id' => $route->origin_stop_id, 'fare_from_origin' => 0],
                ['bus_stop_id' => $route->destination_stop_id, 'fare_from_origin' => ''],
            ];
        }
    }
@endphp

<div class="p-8 max-w-4xl">
    <div class="flex justify-between items-start mb-6">
        <div>
            <h2 class="text-3xl font-bold">Stops &amp; Fares</h2>
            <p class="text-gray-500 mt-1">
                {{ $route->route_name }} · {{ $route->origin->name }} → {{ $route->destination->name }}
            </p>
        </div>
        <a href="{{ route('admin.routes.show', $route) }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
            Back to route
        </a>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 mb-6">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-6 text-sm text-orange-900">
        <p class="font-semibold mb-1">How fares work</p>
        <p>
            Enter the fare from the first stop to each stop, in order. The passenger fare between any two
            stops is the difference, so a passenger who drops halfway pays only for that part of the trip.
        </p>
        <p class="mt-2 text-orange-800">
            Example: Pokuase GHS 0.00 → Amasaman GHS 3.50 → Achimota GHS 7.00 → Circle GHS 10.00.
            A passenger boarding at Amasaman and dropping at Circle pays GHS 6.50.
        </p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.routes.stops.update', $route) }}" method="POST" id="route-stops-form">
            @csrf
            @method('PUT')

            <table class="w-full mb-4">
                <thead>
                    <tr class="text-left border-b bg-gray-50">
                        <th class="p-3 w-16">Order</th>
                        <th class="p-3">Bus stop</th>
                        <th class="p-3 w-48">Fare from first stop (GHS)</th>
                        <th class="p-3 w-20"></th>
                    </tr>
                </thead>
                <tbody id="stop-rows">
                    @foreach($rows as $index => $row)
                        <tr class="border-b stop-row">
                            <td class="p-3 font-semibold text-gray-500 row-number">{{ $index + 1 }}</td>
                            <td class="p-3">
                                <select name="stops[{{ $index }}][bus_stop_id]" class="w-full border rounded px-3 py-2" required>
                                    <option value="">Select stop</option>
                                    @foreach($busStops as $busStop)
                                        <option value="{{ $busStop->id }}" {{ (string) ($row['bus_stop_id'] ?? '') === (string) $busStop->id ? 'selected' : '' }}>
                                            {{ $busStop->name }} ({{ $busStop->area ?? 'N/A' }})
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="p-3">
                                <input type="number" step="0.01" min="0" name="stops[{{ $index }}][fare_from_origin]"
                                       value="{{ $row['fare_from_origin'] ?? '' }}" class="w-full border rounded px-3 py-2" required>
                            </td>
                            <td class="p-3 text-center">
                                <button type="button" class="text-red-600 hover:underline remove-row">Remove</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <button type="button" id="add-stop-row" class="bg-gray-100 text-gray-700 px-4 py-2 rounded hover:bg-gray-200 mb-6">
                + Add stop
            </button>

            <div class="flex gap-4">
                <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">
                    Save stops &amp; fares
                </button>
                <a href="{{ route('admin.routes.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<template id="stop-row-template">
    <tr class="border-b stop-row">
        <td class="p-3 font-semibold text-gray-500 row-number"></td>
        <td class="p-3">
            <select class="w-full border rounded px-3 py-2" required>
                <option value="">Select stop</option>
                @foreach($busStops as $busStop)
                    <option value="{{ $busStop->id }}">{{ $busStop->name }} ({{ $busStop->area ?? 'N/A' }})</option>
                @endforeach
            </select>
        </td>
        <td class="p-3">
            <input type="number" step="0.01" min="0" class="w-full border rounded px-3 py-2" required>
        </td>
        <td class="p-3 text-center">
            <button type="button" class="text-red-600 hover:underline remove-row">Remove</button>
        </td>
    </tr>
</template>

<script>
    const rowsBody = document.getElementById('stop-rows');
    const template = document.getElementById('stop-row-template');

    function renumber() {
        Array.from(rowsBody.querySelectorAll('.stop-row')).forEach((row, index) => {
            row.querySelector('.row-number').textContent = index + 1;
            const select = row.querySelector('select');
            const fare = row.querySelector('input');
            select.name = `stops[${index}][bus_stop_id]`;
            fare.name = `stops[${index}][fare_from_origin]`;
        });
    }

    document.getElementById('add-stop-row').addEventListener('click', () => {
        rowsBody.appendChild(template.content.cloneNode(true));
        renumber();
    });

    rowsBody.addEventListener('click', (event) => {
        if (!event.target.classList.contains('remove-row')) return;
        const rows = rowsBody.querySelectorAll('.stop-row');
        if (rows.length <= 2) return;
        event.target.closest('.stop-row').remove();
        renumber();
    });
</script>
@endsection
