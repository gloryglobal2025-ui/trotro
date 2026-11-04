@extends('admin.layout')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #tracking-map {
        height: 500px;
        width: 100%;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        color: white;
    }
    .status-pending { background-color: #6b7280; }
    .status-accepted { background-color: #3b82f6; }
    .status-driver_arrived { background-color: #f59e0b; }
    .status-in_progress { background-color: #10b981; }
    .status-completed { background-color: #10b981; }
    .status-cancelled { background-color: #ef4444; }
</style>
@endpush

@section('content')
<div class="p-4 sm:p-6 lg:p-8">
<div class="mb-6">
    <a href="{{ route('admin.rides.index') }}" class="text-orange-600 hover:text-orange-800 mb-4 inline-block">
        &larr; Back to Rides
    </a>
    <h1 class="text-2xl font-bold text-gray-800 mt-2">Ride Tracking #{{ $ride->id }}</h1>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Map Section -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Live Tracking</h2>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">Last Updated:</span>
                    <span id="last-update" class="text-sm font-semibold text-gray-700">Loading...</span>
                </div>
            </div>
            <div id="tracking-map"></div>
            <div class="mt-4 flex items-center gap-4 text-sm">
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-green-500 rounded-full"></div>
                    <span>Pickup</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-red-500 rounded-full"></div>
                    <span>Destination</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 bg-blue-500 rounded-full"></div>
                    <span>Driver</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Ride Details -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">Ride Details</h2>

            <div class="space-y-4">
                <div>
                    <label class="text-sm text-gray-500">Status</label>
                    <div class="mt-1">
                        <span class="status-badge status-{{ $ride->status }}">
                            {{ ucfirst(str_replace('_', ' ', $ride->status)) }}
                        </span>
                    </div>
                </div>

                <div>
                    <label class="text-sm text-gray-500">Fare</label>
                    <div class="mt-1 font-bold text-green-600">
                        @if($ride->fare_amount)
                            GHS {{ number_format($ride->fare_amount, 2) }}
                        @else
                            -
                        @endif
                    </div>
                </div>

                <div>
                    <label class="text-sm text-gray-500">Distance</label>
                    <div class="mt-1">
                        @if($ride->distance_km)
                            {{ number_format($ride->distance_km, 2) }} km
                        @else
                            -
                        @endif
                    </div>
                </div>

                <div>
                    <label class="text-sm text-gray-500">Duration</label>
                    <div class="mt-1">
                        @if($ride->duration_minutes)
                            {{ $ride->duration_minutes }} min
                        @else
                            -
                        @endif
                    </div>
                </div>

                <hr>

                <div>
                    <label class="text-sm text-gray-500">Pickup Location</label>
                    <div class="mt-1">
                        <p class="font-semibold">{{ $ride->origin_address }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $ride->origin_latitude }}, {{ $ride->origin_longitude }}
                        </p>
                    </div>
                </div>

                <div>
                    <label class="text-sm text-gray-500">Destination</label>
                    <div class="mt-1">
                        <p class="font-semibold">{{ $ride->destination_address }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $ride->destination_latitude }}, {{ $ride->destination_longitude }}
                        </p>
                    </div>
                </div>

                @if($ride->driver_current_latitude && $ride->driver_current_longitude)
                <div class="bg-blue-50 p-3 rounded-lg">
                    <label class="text-sm text-blue-700 font-semibold">Driver Location</label>
                    <div class="mt-1">
                        <p class="text-xs text-gray-600">
                            {{ $ride->driver_current_latitude }}, {{ $ride->driver_current_longitude }}
                        </p>
                        <p class="text-xs text-gray-500">
                            Last update: {{ $ride->driver_last_location_update ? $ride->driver_last_location_update->diffForHumans() : 'Never' }}
                        </p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Passenger Info -->
        @if($ride->passenger)
        <div class="bg-white rounded-lg shadow p-6 mt-6">
            <h2 class="text-xl font-bold mb-4">Passenger</h2>
            <div class="space-y-2">
                <div>
                    <label class="text-sm text-gray-500">Name</label>
                    <div class="mt-1 font-semibold">{{ $ride->passenger->name }}</div>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Phone</label>
                    <div class="mt-1">{{ $ride->passenger->phone ?? 'N/A' }}</div>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Rating</label>
                    <div class="mt-1">
                        ⭐ {{ number_format($ride->passenger->rating, 1) }}
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Driver Info -->
        @if($ride->driver)
        <div class="bg-white rounded-lg shadow p-6 mt-6">
            <h2 class="text-xl font-bold mb-4">Driver</h2>
            <div class="space-y-2">
                <div>
                    <label class="text-sm text-gray-500">Name</label>
                    <div class="mt-1 font-semibold">{{ $ride->driver->name }}</div>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Phone</label>
                    <div class="mt-1">{{ $ride->driver->phone ?? 'N/A' }}</div>
                </div>
                <div>
                    <label class="text-sm text-gray-500">Rating</label>
                    <div class="mt-1">
                        ⭐ {{ number_format($ride->driver->rating, 1) }}
                    </div>
                </div>
                @if($ride->vehicle)
                <div>
                    <label class="text-sm text-gray-500">Vehicle</label>
                    <div class="mt-1">
                        {{ $ride->vehicle->make }} {{ $ride->vehicle->model }}
                        ({{ $ride->vehicle->license_plate }})
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

</div>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const rideId = {{ $ride->id }};
    let map;
    let driverMarker;
    let pickupMarker;
    let destinationMarker;

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize map
        const pickupLat = {{ $ride->origin_latitude }};
        const pickupLng = {{ $ride->origin_longitude }};
        const destLat = {{ $ride->destination_latitude }};
        const destLng = {{ $ride->destination_longitude }};

        // Calculate center
        const centerLat = (pickupLat + destLat) / 2;
        const centerLng = (pickupLng + destLng) / 2;

        map = L.map('tracking-map').setView([centerLat, centerLng], 12);

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Add pickup marker
        pickupMarker = L.marker([pickupLat, pickupLng], {
            icon: L.divIcon({
                className: 'custom-icon',
                html: '<div style="background-color: #10b981; width: 30px; height: 30px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>',
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            })
        }).addTo(map).bindPopup(@json('Pickup: '.$ride->origin_address));

        // Add destination marker
        destinationMarker = L.marker([destLat, destLng], {
            icon: L.divIcon({
                className: 'custom-icon',
                html: '<div style="background-color: #ef4444; width: 30px; height: 30px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>',
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            })
        }).addTo(map).bindPopup(@json('Destination: '.$ride->destination_address));

        // Add driver marker if location available
        @if($ride->driver_current_latitude && $ride->driver_current_longitude)
            const driverLat = {{ $ride->driver_current_latitude }};
            const driverLng = {{ $ride->driver_current_longitude }};

            driverMarker = L.marker([driverLat, driverLng], {
                icon: L.divIcon({
                    className: 'custom-icon',
                    html: '<div style="background-color: #3b82f6; width: 40px; height: 40px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center;">🚗</div>',
                    iconSize: [40, 40],
                    iconAnchor: [20, 20]
                })
            }).addTo(map).bindPopup('Driver Current Location');
        @endif

        // Fit bounds to show all markers
        const bounds = L.latLngBounds([
            [pickupLat, pickupLng],
            [destLat, destLng]
        ]);
        @if($ride->driver_current_latitude && $ride->driver_current_longitude)
            bounds.extend([{{ $ride->driver_current_latitude }}, {{ $ride->driver_current_longitude }}]);
        @endif
        map.fitBounds(bounds, { padding: [50, 50] });

        // Start polling for driver location updates
        setInterval(updateDriverLocation, 10000); // Update every 10 seconds
        updateDriverLocation(); // Initial call
    });

    function updateDriverLocation() {
        fetch(`/admin/rides/${rideId}/tracking-data`)
            .then(response => response.json())
            .then(data => {
                if (data.driver_location.latitude && data.driver_location.longitude) {
                    const driverLat = data.driver_location.latitude;
                    const driverLng = data.driver_location.longitude;

                    // Update or create driver marker
                    if (driverMarker) {
                        driverMarker.setLatLng([driverLat, driverLng]);
                    } else {
                        driverMarker = L.marker([driverLat, driverLng], {
                            icon: L.divIcon({
                                className: 'custom-icon',
                                html: '<div style="background-color: #3b82f6; width: 40px; height: 40px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center;">🚗</div>',
                                iconSize: [40, 40],
                                iconAnchor: [20, 20]
                            })
                        }).addTo(map).bindPopup('Driver Current Location');
                    }

                    // Update last update time
                    const lastUpdate = new Date(data.driver_location.last_update);
                    document.getElementById('last-update').textContent = lastUpdate.toLocaleTimeString();
                } else {
                    document.getElementById('last-update').textContent = 'No location yet';
                }
            })
            .catch(error => console.error('Error updating driver location:', error));
    }
</script>
@endpush
@endsection
