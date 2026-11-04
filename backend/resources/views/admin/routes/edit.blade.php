@extends('admin.layout')

@section('content')
<div class="p-8">
    <h2 class="text-3xl font-bold mb-6">Edit Route</h2>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.routes.update', $route) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Origin *</label>
                <select name="origin_stop_id" class="w-full border rounded px-3 py-2" required>
                    <option value="">Select Origin</option>
                    @foreach($busStops as $busStop)
                        <option value="{{ $busStop->id }}" {{ $route->origin_stop_id == $busStop->id ? 'selected' : '' }}>
                            {{ $busStop->name }} ({{ $busStop->area ?? 'N/A' }})
                        </option>
                    @endforeach
                </select>
                @error('origin_stop_id') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Destination *</label>
                <select name="destination_stop_id" class="w-full border rounded px-3 py-2" required>
                    <option value="">Select Destination</option>
                    @foreach($busStops as $busStop)
                        <option value="{{ $busStop->id }}" {{ $route->destination_stop_id == $busStop->id ? 'selected' : '' }}>
                            {{ $busStop->name }} ({{ $busStop->area ?? 'N/A' }})
                        </option>
                    @endforeach
                </select>
                @error('destination_stop_id') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Route Name *</label>
                <input type="text" name="route_name" value="{{ $route->route_name }}" class="w-full border rounded px-3 py-2" required>
                @error('route_name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Description</label>
                <textarea name="description" class="w-full border rounded px-3 py-2" rows="3">{{ $route->description }}</textarea>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Estimated Duration (minutes)</label>
                <input type="number" name="estimated_duration" value="{{ $route->estimated_duration }}" class="w-full border rounded px-3 py-2">
            </div>

            <div class="flex gap-4">
                <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">
                    Update Route
                </button>
                <a href="{{ route('admin.routes.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
