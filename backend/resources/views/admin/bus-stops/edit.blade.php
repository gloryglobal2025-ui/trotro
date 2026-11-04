@extends('admin.layout')

@section('content')
<div class="p-8">
    <h2 class="text-3xl font-bold mb-6">Edit Bus Stop</h2>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.bus-stops.update', $busStop) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Name *</label>
                <input type="text" name="name" value="{{ $busStop->name }}" class="w-full border rounded px-3 py-2" required>
                @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Location</label>
                <input type="text" name="location" value="{{ $busStop->location }}" class="w-full border rounded px-3 py-2">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Latitude</label>
                <input type="number" step="any" name="latitude" value="{{ $busStop->latitude }}" class="w-full border rounded px-3 py-2">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Longitude</label>
                <input type="number" step="any" name="longitude" value="{{ $busStop->longitude }}" class="w-full border rounded px-3 py-2">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Area</label>
                <input type="text" name="area" value="{{ $busStop->area }}" class="w-full border rounded px-3 py-2">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Photo</label>
                @if($busStop->image_url)
                    <img src="{{ $busStop->image_url }}" alt="{{ $busStop->name }}" class="mb-3 h-32 w-48 rounded-lg border object-cover">
                @endif
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="w-full border rounded px-3 py-2 bg-white">
                <p class="mt-1 text-xs text-gray-500">Upload a new photo to replace the current one. JPG, PNG or WebP up to 2 MB.</p>
                @error('image') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex gap-4">
                <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">
                    Update Bus Stop
                </button>
                <a href="{{ route('admin.bus-stops.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
