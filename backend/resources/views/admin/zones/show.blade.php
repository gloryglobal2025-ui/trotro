@extends('admin.layout')

@section('content')
<div class="p-4 sm:p-6 lg:p-8">
<div class="mb-6">
    <a href="{{ route('admin.zones.index') }}" class="text-orange-600 hover:text-orange-800 mb-4 inline-block">
        &larr; Back to Zones
    </a>
    <h1 class="text-2xl font-bold text-gray-800 mt-2">Zone: {{ $zone->name }}</h1>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Zone Details -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Zone Details</h2>
        <div class="space-y-3">
            <div class="flex justify-between">
                <span class="text-gray-600">Name:</span>
                <span class="font-medium">{{ $zone->name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Code:</span>
                <span class="font-medium font-mono">{{ $zone->code }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Center:</span>
                <span class="font-medium">{{ $zone->center_latitude }}, {{ $zone->center_longitude }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Radius:</span>
                <span class="font-medium">{{ $zone->radius_km }} km</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Status:</span>
                @if($zone->is_active)
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                @else
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                @endif
            </div>
            @if($zone->description)
            <div class="mt-3">
                <span class="text-gray-600 block text-sm">Description:</span>
                <p class="font-medium">{{ $zone->description }}</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Bus Stops -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Bus Stops ({{ $zone->busStops->count() }})</h2>
        @if($zone->busStops->count() > 0)
        <div class="space-y-2">
            @foreach($zone->busStops as $busStop)
            <div class="p-3 bg-gray-50 rounded">
                <p class="font-medium">{{ $busStop->name }}</p>
                <p class="text-sm text-gray-500">{{ $busStop->location }}</p>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-gray-500">No bus stops in this zone.</p>
        @endif
    </div>
</div>

<!-- Zone Fares -->
<div class="mt-6 bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Zone Fares</h2>
    @if($zone->fromZoneFares->count() > 0 || $zone->toZoneFares->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">From</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">To</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Base Fare</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rate/km</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Min Fare</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($zone->fromZoneFares as $fare)
                <tr>
                    <td class="px-4 py-2 text-sm">{{ $zone->name }}</td>
                    <td class="px-4 py-2 text-sm">{{ $fare->toZone->name }}</td>
                    <td class="px-4 py-2 text-sm">GHS {{ number_format($fare->base_fare, 2) }}</td>
                    <td class="px-4 py-2 text-sm">GHS {{ number_format($fare->rate_per_km, 2) }}</td>
                    <td class="px-4 py-2 text-sm">GHS {{ number_format($fare->min_fare, 2) }}</td>
                    <td class="px-4 py-2">
                        @if($fare->is_active)
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                        @else
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p class="text-gray-500">No zone fares configured.</p>
    @endif
</div>
</div>
@endsection
