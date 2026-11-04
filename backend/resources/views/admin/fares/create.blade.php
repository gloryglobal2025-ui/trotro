@extends('admin.layout')

@section('content')
<div class="p-8">
    <h2 class="text-3xl font-bold mb-6">Create Fare</h2>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.fares.store') }}" method="POST">
            @csrf

            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Route *</label>
                <select name="route_id" class="w-full border rounded px-3 py-2" required>
                    <option value="">Select Route</option>
                    @foreach($routes as $route)
                        <option value="{{ $route->id }}">
                            {{ $route->route_name }} ({{ $route->origin->name }} to {{ $route->destination->name }})
                        </option>
                    @endforeach
                </select>
                @error('route_id') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Amount (GHS) *</label>
                <input type="number" step="0.01" name="amount" value="{{ old('amount') }}" class="w-full border rounded px-3 py-2" required>
                @error('amount') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Fare Type</label>
                <input type="text" name="fare_type" value="{{ old('fare_type', 'regular') }}" class="w-full border rounded px-3 py-2">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Description</label>
                <textarea name="description" class="w-full border rounded px-3 py-2" rows="3">{{ old('description') }}</textarea>
            </div>

            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }} class="mr-2">
                    <span class="text-gray-700">Active</span>
                </label>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Effective Date</label>
                <input type="date" name="effective_date" value="{{ old('effective_date') }}" class="w-full border rounded px-3 py-2">
            </div>

            <div class="flex gap-4">
                <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">
                    Create Fare
                </button>
                <a href="{{ route('admin.fares.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
