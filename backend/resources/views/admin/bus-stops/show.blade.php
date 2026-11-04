@extends('admin.layout')

@section('content')
<div class="p-8">
    <h2 class="text-3xl font-bold mb-6">Bus Stop Details</h2>

    <div class="bg-white rounded-lg shadow p-6">
        @if($busStop->image_url)
            <img src="{{ $busStop->image_url }}" alt="{{ $busStop->name }}" class="mb-6 h-64 w-full max-w-xl rounded-xl object-cover">
        @endif
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-gray-500">ID</label>
                <p class="font-semibold">{{ $busStop->id }}</p>
            </div>
            <div>
                <label class="text-gray-500">Name</label>
                <p class="font-semibold">{{ $busStop->name }}</p>
            </div>
            <div>
                <label class="text-gray-500">Location</label>
                <p class="font-semibold">{{ $busStop->location ?? '-' }}</p>
            </div>
            <div>
                <label class="text-gray-500">Area</label>
                <p class="font-semibold">{{ $busStop->area ?? '-' }}</p>
            </div>
            <div>
                <label class="text-gray-500">Latitude</label>
                <p class="font-semibold">{{ $busStop->latitude ?? '-' }}</p>
            </div>
            <div>
                <label class="text-gray-500">Longitude</label>
                <p class="font-semibold">{{ $busStop->longitude ?? '-' }}</p>
            </div>
        </div>

        <div class="mt-6 flex gap-4">
            <a href="{{ route('admin.bus-stops.edit', $busStop) }}" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">
                Edit
            </a>
            <a href="{{ route('admin.bus-stops.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
                Back
            </a>
        </div>
    </div>
</div>
@endsection
