@extends('admin.layout')

@section('title', 'Add Vehicle')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <a href="{{ route('admin.vehicles.index') }}" class="text-orange-600 hover:text-orange-800">← Back to Vehicles</a>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-6">Add New Vehicle</h1>

    <form action="{{ route('admin.vehicles.store') }}" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-lg shadow">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Driver</label>
                <select name="driver_id" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <option value="">Select Driver</option>
                    @foreach($drivers as $driver)
                    <option value="{{ $driver->id }}">{{ $driver->name }} ({{ $driver->email }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
                <select name="brand_id" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <option value="">Select Brand (Optional)</option>
                    @foreach($brands as $brand)
                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Make</label>
                <input type="text" name="make" value="{{ old('make') }}" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Model</label>
                <input type="text" name="model" value="{{ old('model') }}" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                <input type="number" name="year" value="{{ old('year') }}" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">License Plate</label>
                <input type="text" name="license_plate" value="{{ old('license_plate') }}" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Color</label>
                <input type="text" name="color" value="{{ old('color') }}" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Capacity</label>
                <input type="number" name="capacity" value="{{ old('capacity', 4) }}" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Vehicle Type</label>
                <select name="vehicle_type" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <option value="sedan">Sedan</option>
                    <option value="suv">SUV</option>
                    <option value="minivan">Minivan</option>
                    <option value="bus">Bus</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Vehicle Photo</label>
                <input type="file" name="vehicle_image" accept="image/jpeg,image/png,image/webp" class="w-full px-3 py-2 border rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                <p class="mt-1 text-xs text-gray-500">JPG, PNG or WebP up to 2 MB.</p>
                @error('vehicle_image') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Attributes</label>
            @foreach($attributes as $type => $attrs)
            <div class="mb-4">
                <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ ucfirst($type) }}</h4>
                <div class="flex flex-wrap gap-2">
                    @foreach($attrs as $attr)
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="attributes[]" value="{{ $attr->id }}" class="mr-2">
                        <span class="text-sm">{{ $attr->name }} ({{ $attr->value }})</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-6">
            <button type="submit" class="bg-orange-600 text-white px-6 py-2 rounded-lg hover:bg-orange-700">
                Create Vehicle
            </button>
        </div>
    </form>
</div>
@endsection
