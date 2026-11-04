@extends('admin.layout')

@section('content')
<div class="p-8">
    <h2 class="text-3xl font-bold mb-6">Route Details</h2>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-gray-500">ID</label>
                <p class="font-semibold">{{ $route->id }}</p>
            </div>
            <div>
                <label class="text-gray-500">Route Name</label>
                <p class="font-semibold">{{ $route->route_name }}</p>
            </div>
            <div>
                <label class="text-gray-500">Origin</label>
                <p class="font-semibold">{{ $route->origin->name }}</p>
            </div>
            <div>
                <label class="text-gray-500">Destination</label>
                <p class="font-semibold">{{ $route->destination->name }}</p>
            </div>
            <div>
                <label class="text-gray-500">Description</label>
                <p class="font-semibold">{{ $route->description ?? '-' }}</p>
            </div>
            <div>
                <label class="text-gray-500">Estimated Duration</label>
                <p class="font-semibold">{{ $route->estimated_duration ?? '-' }} min</p>
            </div>
        </div>

        <div class="mt-6 flex gap-4">
            <a href="{{ route('admin.routes.edit', $route) }}" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">
                Edit
            </a>
            <a href="{{ route('admin.routes.stops', $route) }}" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                Stops &amp; Fares
            </a>
            <a href="{{ route('admin.routes.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
                Back
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-xl font-bold mb-4">Stops Along This Route</h3>
        @if($route->stops->count() > 0)
            <table class="w-full">
                <thead>
                    <tr class="text-left border-b">
                        <th class="p-3">Order</th>
                        <th class="p-3">Stop</th>
                        <th class="p-3">Fare From Origin</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($route->stops as $stop)
                        <tr class="border-b">
                            <td class="p-3">{{ $stop->sequence }}</td>
                            <td class="p-3 font-semibold">{{ $stop->stop->name }}</td>
                            <td class="p-3">GHS {{ number_format($stop->fare_from_origin, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-gray-500">
                No stops configured yet.
                <a href="{{ route('admin.routes.stops', $route) }}" class="text-orange-600 hover:underline">Add stops and fares</a>.
            </p>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-xl font-bold mb-4">Fares</h3>
        @if($route->fares->count() > 0)
            <table class="w-full">
                <thead>
                    <tr class="text-left border-b">
                        <th class="p-3">Fare Type</th>
                        <th class="p-3">Amount</th>
                        <th class="p-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($route->fares as $fare)
                        <tr class="border-b">
                            <td class="p-3">{{ $fare->fare_type }}</td>
                            <td class="p-3">GHS {{ number_format($fare->amount, 2) }}</td>
                            <td class="p-3">
                                @if($fare->is_active)
                                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded">Active</span>
                                @else
                                    <span class="bg-red-100 text-red-800 px-2 py-1 rounded">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-gray-500">No fares found for this route.</p>
        @endif
    </div>
</div>
@endsection
