@extends('admin.layout')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
<style>
    #zone-map {
        height: 500px;
        width: 100%;
        border-radius: 8px;
        margin-bottom: 20px;
    }
</style>
@endpush

@section('content')
<div class="p-4 sm:p-6 lg:p-8">
<div class="mb-6">
    <a href="{{ route('admin.zones.index') }}" class="text-orange-600 hover:text-orange-800 mb-4 inline-block">
        &larr; Back to Zones
    </a>
    <h1 class="text-2xl font-bold text-gray-800 mt-2">Create Zone</h1>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <form action="{{ route('admin.zones.store') }}" method="POST">
        @csrf
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Select Zone Center on Map</label>
            <p class="text-sm text-gray-500 mb-2">Click on the map to set the zone center point</p>
            <div id="zone-map"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                <input type="text" name="name" required class="w-full border rounded px-3 py-2" placeholder="e.g., Central Accra">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Code</label>
                <input type="text" name="code" required class="w-full border rounded px-3 py-2 font-mono" placeholder="e.g., ACC-CENTRAL">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" rows="3" class="w-full border rounded px-3 py-2" placeholder="Zone description"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Center Latitude</label>
                <input type="number" step="any" name="center_latitude" id="center_latitude" required class="w-full border rounded px-3 py-2" placeholder="e.g., 5.5716" value="5.6037">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Center Longitude</label>
                <input type="number" step="any" name="center_longitude" id="center_longitude" required class="w-full border rounded px-3 py-2" placeholder="e.g., -0.1870" value="-0.1870">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Radius (km)</label>
                <input type="number" step="0.1" name="radius_km" required class="w-full border rounded px-3 py-2" placeholder="e.g., 5.0">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="is_active" class="w-full border rounded px-3 py-2">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </div>
        <div class="mt-6">
            <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">
                Create Zone
            </button>
        </div>
    </form>
</div>

</div>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize map centered on Accra
        const map = L.map('zone-map').setView([5.6037, -0.1870], 12);

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Add drawing controls
        const drawnItems = new L.FeatureGroup();
        map.addLayer(drawnItems);

        const drawControl = new L.Control.Draw({
            draw: {
                polygon: {
                    allowIntersection: false,
                    showArea: true
                },
                circle: {
                    showArea: true
                },
                rectangle: true,
                marker: false,
                polyline: false,
                circlemarker: false
            },
            edit: {
                featureGroup: drawnItems,
                remove: true
            }
        });
        map.addControl(drawControl);

        // Handle shape creation
        map.on(L.Draw.Event.CREATED, function(event) {
            const layer = event.layer;
            drawnItems.addLayer(layer);

            // Clear previous shapes (only allow one shape)
            drawnItems.eachLayer(function(l) {
                if (l !== layer) {
                    drawnItems.removeLayer(l);
                }
            });

            // Calculate center from shape
            const bounds = layer.getBounds();
            const center = bounds.getCenter();

            // Update form inputs
            document.getElementById('center_latitude').value = center.lat.toFixed(6);
            document.getElementById('center_longitude').value = center.lng.toFixed(6);

            // If it's a circle, calculate radius
            if (layer instanceof L.Circle) {
                const radiusKm = (layer.getRadius() / 1000).toFixed(2);
                const radiusInput = document.querySelector('input[name="radius_km"]');
                if (radiusInput) {
                    radiusInput.value = radiusKm;
                }
            }
        });

        // Handle shape deletion
        map.on(L.Draw.Event.DELETED, function(event) {
            // Reset form values
            document.getElementById('center_latitude').value = '5.6037';
            document.getElementById('center_longitude').value = '-0.1870';
        });
    });
</script>
@endpush
@endsection
