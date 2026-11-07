@extends('admin.layout')

@section('title', 'New Vehicle Requests')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <a href="{{ route('admin.vehicles.index') }}" class="text-orange-600 hover:text-orange-800">← Back to Vehicles</a>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-6">New Vehicle Requests</h1>

    @forelse($vehicles as $vehicle)
    <div class="bg-white rounded-lg shadow p-6 mb-4">
        <div class="flex flex-col lg:flex-row gap-6">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 mb-2">
                    <h2 class="text-lg font-semibold text-gray-900">{{ $vehicle->make }} {{ $vehicle->model }} ({{ $vehicle->year }})</h2>
                    <span class="px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800">Pending</span>
                </div>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1 text-sm">
                    <div><dt class="inline text-gray-500">License plate:</dt> <dd class="inline font-mono font-medium text-gray-900">{{ $vehicle->license_plate }}</dd></div>
                    <div><dt class="inline text-gray-500">Type:</dt> <dd class="inline text-gray-900">{{ ucfirst($vehicle->vehicle_type) }}</dd></div>
                    <div><dt class="inline text-gray-500">Color:</dt> <dd class="inline text-gray-900">{{ $vehicle->color }}</dd></div>
                    <div><dt class="inline text-gray-500">Capacity:</dt> <dd class="inline text-gray-900">{{ $vehicle->capacity }} seats</dd></div>
                    <div><dt class="inline text-gray-500">Brand:</dt> <dd class="inline text-gray-900">{{ $vehicle->brand?->name ?? '—' }}</dd></div>
                    <div><dt class="inline text-gray-500">Submitted:</dt> <dd class="inline text-gray-900">{{ $vehicle->created_at->format('M j, Y g:i A') }}</dd></div>
                </dl>
                <div class="mt-3 pt-3 border-t border-gray-100 text-sm">
                    <span class="text-gray-500">Driver:</span>
                    <span class="font-medium text-gray-900">{{ $vehicle->driver?->name ?? 'Unknown driver' }}</span>
                    <span class="text-gray-500">· {{ $vehicle->driver?->phone ?? $vehicle->driver?->email ?? '—' }}</span>
                </div>
                <div class="mt-3 grid grid-cols-3 gap-3">
                    @foreach([['vehicle_image_url', 'Vehicle photo'], ['registration_image_url', 'Registration'], ['roadworthy_image_url', 'Roadworthy']] as [$attribute, $label])
                    <div>
                        <p class="text-xs font-medium text-gray-500 mb-1">{{ $label }}</p>
                        @if($vehicle->{$attribute})
                        <a href="{{ $vehicle->{$attribute} }}" target="_blank" rel="noopener">
                            <img src="{{ $vehicle->{$attribute} }}" alt="{{ $label }}" class="h-24 w-full object-cover rounded-lg border border-gray-200">
                        </a>
                        @else
                        <div class="h-24 w-full rounded-lg border border-dashed border-red-300 bg-red-50 flex items-center justify-center text-xs text-red-600 text-center px-2">Not uploaded</div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="lg:w-64 shrink-0 space-y-3">
                <form action="{{ route('admin.vehicles.new-requests.approve', $vehicle) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700" onclick="return confirm('Approve this vehicle?')">Approve</button>
                </form>
                <form action="{{ route('admin.vehicles.new-requests.reject', $vehicle) }}" method="POST">
                    @csrf
                    <textarea name="rejection_reason" rows="2" placeholder="Rejection reason (optional)" class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                    <button type="submit" class="mt-2 w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700" onclick="return confirm('Reject this vehicle?')">Reject</button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-lg shadow p-6 text-center text-gray-500">No pending vehicle requests</div>
    @endforelse

    {{ $vehicles->links() }}
</div>
@endsection