@extends('admin.layout')

@section('content')
<div class="p-4 sm:p-6 lg:p-8">
<div class="mb-6">
    <a href="{{ route('admin.rides.index') }}" class="text-orange-600 hover:text-orange-800 mb-4 inline-block">
        &larr; Back to Rides
    </a>
    <h1 class="text-2xl font-bold text-gray-800 mt-2">Ride #{{ $ride->id }}</h1>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Ride Details -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Ride Details</h2>
        <div class="space-y-3">
            <div class="flex justify-between">
                <span class="text-gray-600">Status:</span>
                @php
                    $statusColors = [
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'accepted' => 'bg-blue-100 text-blue-800',
                        'driver_arrived' => 'bg-purple-100 text-purple-800',
                        'in_progress' => 'bg-indigo-100 text-indigo-800',
                        'completed' => 'bg-green-100 text-green-800',
                        'cancelled' => 'bg-red-100 text-red-800',
                    ];
                    $statusClass = $statusColors[$ride->status] ?? 'bg-gray-100 text-gray-800';
                @endphp
                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusClass }}">
                    {{ ucfirst(str_replace('_', ' ', $ride->status)) }}
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Payment Status:</span>
                <span class="font-medium">{{ ucfirst($ride->payment_status) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Payment Method:</span>
                <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $ride->payment_method)) }}</span>
            </div>
            @if($ride->fare_amount)
            <div class="flex justify-between">
                <span class="text-gray-600">Fare:</span>
                <span class="font-medium text-green-600">GHS {{ number_format($ride->fare_amount, 2) }}</span>
            </div>
            @endif
            @if($ride->distance_km)
            <div class="flex justify-between">
                <span class="text-gray-600">Distance:</span>
                <span class="font-medium">{{ number_format($ride->distance_km, 2) }} km</span>
            </div>
            @endif
            @if($ride->duration_minutes)
            <div class="flex justify-between">
                <span class="text-gray-600">Duration:</span>
                <span class="font-medium">{{ $ride->duration_minutes }} min</span>
            </div>
            @endif
            <div class="flex justify-between">
                <span class="text-gray-600">Requested:</span>
                <span class="font-medium">{{ $ride->requested_at?->format('M d, Y H:i') ?? 'N/A' }}</span>
            </div>
            @if($ride->accepted_at)
            <div class="flex justify-between">
                <span class="text-gray-600">Accepted:</span>
                <span class="font-medium">{{ $ride->accepted_at->format('M d, Y H:i') }}</span>
            </div>
            @endif
            @if($ride->pickup_at)
            <div class="flex justify-between">
                <span class="text-gray-600">Pickup:</span>
                <span class="font-medium">{{ $ride->pickup_at->format('M d, Y H:i') }}</span>
            </div>
            @endif
            @if($ride->dropoff_at)
            <div class="flex justify-between">
                <span class="text-gray-600">Dropoff:</span>
                <span class="font-medium">{{ $ride->dropoff_at->format('M d, Y H:i') }}</span>
            </div>
            @endif
        </div>
    </div>

    <!-- Route Information -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Route Information</h2>
        <div class="space-y-4">
            <div>
                <span class="text-gray-600 block text-sm">Pickup Location</span>
                <p class="font-medium">{{ $ride->origin_address }}</p>
                <p class="text-xs text-gray-500">
                    Lat: {{ $ride->origin_latitude }}, Lng: {{ $ride->origin_longitude }}
                </p>
            </div>
            <div class="border-t pt-4">
                <span class="text-gray-600 block text-sm">Destination</span>
                <p class="font-medium">{{ $ride->destination_address }}</p>
                <p class="text-xs text-gray-500">
                    Lat: {{ $ride->destination_latitude }}, Lng: {{ $ride->destination_longitude }}
                </p>
            </div>
        </div>
    </div>

    <!-- Passenger Information -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Passenger</h2>
        @if($ride->passenger)
        <div class="space-y-2">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center mr-3">
                    <span class="text-orange-600 font-semibold">{{ substr($ride->passenger->name, 0, 1) }}</span>
                </div>
                <div>
                    <p class="font-medium">{{ $ride->passenger->name }}</p>
                    <p class="text-sm text-gray-500">{{ $ride->passenger->phone }}</p>
                </div>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Rating:</span>
                <span class="font-medium">{{ $ride->passenger->rating ?? 'N/A' }}/5.0</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Total Rides:</span>
                <span class="font-medium">{{ $ride->passenger->total_rides ?? 0 }}</span>
            </div>
        </div>
        @else
        <p class="text-gray-500">No passenger information available.</p>
        @endif
    </div>

    <!-- Driver Information -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Driver</h2>
        @if($ride->driver)
        <div class="space-y-2">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-3">
                    <span class="text-green-600 font-semibold">{{ substr($ride->driver->name, 0, 1) }}</span>
                </div>
                <div>
                    <p class="font-medium">{{ $ride->driver->name }}</p>
                    <p class="text-sm text-gray-500">{{ $ride->driver->phone }}</p>
                </div>
            </div>
            @if($ride->vehicle)
            <div class="mt-3 p-3 bg-gray-50 rounded">
                <p class="text-sm font-medium">{{ $ride->vehicle->make }} {{ $ride->vehicle->model }}</p>
                <p class="text-xs text-gray-500">{{ $ride->vehicle->license_plate }} - {{ $ride->vehicle->color }}</p>
            </div>
            @endif
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Rating:</span>
                <span class="font-medium">{{ $ride->driver->rating ?? 'N/A' }}/5.0</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Total Rides:</span>
                <span class="font-medium">{{ $ride->driver->total_rides ?? 0 }}</span>
            </div>
        </div>
        @else
        <p class="text-gray-500">No driver assigned yet.</p>
        @endif
    </div>
</div>

<!-- Update Status Form -->
@if(in_array($ride->status, ['pending', 'accepted', 'driver_arrived', 'in_progress']))
<div class="mt-6 bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Update Status</h2>
    <form action="{{ route('admin.rides.update', $ride) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="flex items-center space-x-4">
            <select name="status" class="border rounded px-3 py-2">
                <option value="pending" {{ $ride->status === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="accepted" {{ $ride->status === 'accepted' ? 'selected' : '' }}>Accepted</option>
                <option value="driver_arrived" {{ $ride->status === 'driver_arrived' ? 'selected' : '' }}>Driver Arrived</option>
                <option value="in_progress" {{ $ride->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="completed" {{ $ride->status === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ $ride->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
            <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">
                Update Status
            </button>
            @if(in_array($ride->status, ['accepted', 'driver_arrived', 'in_progress']))
            <a href="{{ route('admin.rides.tracking', $ride) }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Track Live
            </a>
            @endif
        </div>
    </form>
</div>
@endif

<!-- Ratings -->
@if($ride->driver_rating || $ride->passenger_rating)
<div class="mt-6 bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Ratings</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @if($ride->driver_rating)
        <div>
            <span class="text-gray-600 block text-sm">Driver Rating:</span>
            <p class="font-medium text-yellow-600">{{ $ride->driver_rating }}/5.0</p>
            @if($ride->driver_comment)
            <p class="text-sm text-gray-500 italic">"{{ $ride->driver_comment }}"</p>
            @endif
        </div>
        @endif
        @if($ride->passenger_rating)
        <div>
            <span class="text-gray-600 block text-sm">Passenger Rating:</span>
            <p class="font-medium text-yellow-600">{{ $ride->passenger_rating }}/5.0</p>
            @if($ride->passenger_comment)
            <p class="text-sm text-gray-500 italic">"{{ $ride->passenger_comment }}"</p>
            @endif
        </div>
        @endif
    </div>
</div>
@endif
</div>
@endsection
