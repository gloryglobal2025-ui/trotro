@extends('admin.layout')

@section('content')
<div class="p-8">
    <h2 class="text-3xl font-bold mb-6">Create Bus Stop</h2>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.bus-stops.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Name *</label>
                <input type="text" name="name" value="{{ old('name') }}" class="w-full border rounded px-3 py-2" required>
                @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Location</label>
                <input type="text" name="location" value="{{ old('location') }}" class="w-full border rounded px-3 py-2">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Latitude</label>
                <input type="number" step="any" name="latitude" value="{{ old('latitude') }}" class="w-full border rounded px-3 py-2">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Longitude</label>
                <input type="number" step="any" name="longitude" value="{{ old('longitude') }}" class="w-full border rounded px-3 py-2">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Area</label>
                <input type="text" name="area" value="{{ old('area') }}" class="w-full border rounded px-3 py-2">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Photo</label>
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="w-full border rounded px-3 py-2 bg-white">
                <p class="mt-1 text-xs text-gray-500">JPG, PNG or WebP up to 2 MB. Riders will see this photo as their stop.</p>
                @error('image') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex gap-4">
                <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">
                    Create Bus Stop
                </button>
                <a href="{{ route('admin.bus-stops.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
