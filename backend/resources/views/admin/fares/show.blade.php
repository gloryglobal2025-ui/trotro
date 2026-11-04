@extends('admin.layout')

@section('content')
<div class="p-8">
    <h2 class="text-3xl font-bold mb-6">Fare Details</h2>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-gray-500">ID</label>
                <p class="font-semibold">{{ $fare->id }}</p>
            </div>
            <div>
                <label class="text-gray-500">Route</label>
                <p class="font-semibold">{{ $fare->route->route_name }}</p>
            </div>
            <div>
                <label class="text-gray-500">Fare Type</label>
                <p class="font-semibold">{{ $fare->fare_type }}</p>
            </div>
            <div>
                <label class="text-gray-500">Amount</label>
                <p class="font-semibold text-2xl text-green-600">GHS {{ number_format($fare->amount, 2) }}</p>
            </div>
            <div>
                <label class="text-gray-500">Status</label>
                <p class="font-semibold">
                    @if($fare->is_active)
                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded">Active</span>
                    @else
                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded">Inactive</span>
                    @endif
                </p>
            </div>
            <div>
                <label class="text-gray-500">Effective Date</label>
                <p class="font-semibold">{{ $fare->effective_date ?? '-' }}</p>
            </div>
        </div>

        @if($fare->description)
            <div class="mt-4">
                <label class="text-gray-500">Description</label>
                <p class="font-semibold">{{ $fare->description }}</p>
            </div>
        @endif

        <div class="mt-6 flex gap-4">
            <a href="{{ route('admin.fares.edit', $fare) }}" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">
                Edit
            </a>
            <a href="{{ route('admin.fares.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
                Back
            </a>
        </div>
    </div>
</div>
@endsection
