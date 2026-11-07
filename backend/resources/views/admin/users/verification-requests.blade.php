@extends('admin.layout')

@section('title', 'Driver Verification')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <a href="{{ route('admin.users.index') }}" class="text-orange-600 hover:text-orange-800">← Back to Users</a>
    </div>

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Driver Verification</h1>
            <p class="text-sm text-gray-500 mt-1">Check the licence, Ghana Card and vehicle papers before a driver can accept rides.</p>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'unverified' => 'Unverified'] as $key => $label)
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs uppercase tracking-wider text-gray-500">{{ $label }}</p>
            <p class="text-2xl font-bold text-gray-800">{{ $counts[$key] }}</p>
        </div>
        @endforeach
    </div>

    @forelse($drivers as $driver)
    <div class="bg-white rounded-lg shadow p-6 mb-4">
        <div class="flex flex-col lg:flex-row gap-6">
            <div class="lg:w-64 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-full bg-orange-100 flex items-center justify-center overflow-hidden shrink-0">
                        @if($driver->profile_image_url)
                        <img src="{{ $driver->profile_image_url }}" alt="{{ $driver->name }}" class="h-12 w-12 object-cover">
                        @else
                        <span class="text-orange-600 font-bold">{{ mb_substr($driver->name, 0, 1) }}</span>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-900 truncate">{{ $driver->name }}</p>
                        <p class="text-sm text-gray-500">{{ $driver->phone ?? 'No phone' }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ $driver->email }}</p>
                    </div>
                </div>
                <p class="mt-3 text-xs text-gray-500">
                    Submitted {{ $driver->verification_submitted_at?->format('M j, Y g:i A') ?? '—' }}
                </p>
                <div class="mt-4 space-y-3">
                    <form action="{{ route('admin.users.verification-requests.approve', $driver) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700" onclick="return confirm('Approve this driver?')">Approve driver</button>
                    </form>
                    <form action="{{ route('admin.users.verification-requests.reject', $driver) }}" method="POST">
                        @csrf
                        <textarea name="verification_notes" rows="2" placeholder="Reason (shown to the driver)" class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                        <button type="submit" class="mt-2 w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700" onclick="return confirm('Reject this verification?')">Reject</button>
                    </form>
                </div>
            </div>

            <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="rounded-lg border border-gray-100 p-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Driver's licence</h3>
                    <dl class="text-sm space-y-1">
                        <div><dt class="inline text-gray-500">Number:</dt> <dd class="inline font-mono font-medium text-gray-900">{{ $driver->license_number ?? '—' }}</dd></div>
                        <div>
                            <dt class="inline text-gray-500">Expires:</dt>
                            <dd class="inline font-medium {{ $driver->license_expiry && $driver->license_expiry->isPast() ? 'text-red-600' : 'text-gray-900' }}">
                                {{ $driver->license_expiry?->format('M j, Y') ?? '—' }}
                            </dd>
                        </div>
                    </dl>
                    @if($driver->license_image_url)
                    <a href="{{ $driver->license_image_url }}" target="_blank" rel="noopener" class="mt-3 block">
                        <img src="{{ $driver->license_image_url }}" alt="Licence photo" class="h-40 w-full object-cover rounded-lg border border-gray-200">
                    </a>
                    @else
                    <p class="mt-3 text-sm text-red-500">No licence photo uploaded.</p>
                    @endif
                </div>

                <div class="rounded-lg border border-gray-100 p-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Ghana Card</h3>
                    <dl class="text-sm space-y-1">
                        <div><dt class="inline text-gray-500">Number:</dt> <dd class="inline font-mono font-medium text-gray-900">{{ $driver->ghana_card_number ?? '—' }}</dd></div>
                    </dl>
                    @if($driver->ghana_card_image_url)
                    <a href="{{ $driver->ghana_card_image_url }}" target="_blank" rel="noopener" class="mt-3 block">
                        <img src="{{ $driver->ghana_card_image_url }}" alt="Ghana Card photo" class="h-40 w-full object-cover rounded-lg border border-gray-200">
                    </a>
                    @else
                    <p class="mt-3 text-sm text-red-500">No Ghana Card photo uploaded.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-4 pt-4 border-t border-gray-100">
            @if($driver->vehicle)
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-3">
                <div>
                    <h3 class="text-sm font-semibold text-gray-700">
                        Vehicle: {{ $driver->vehicle->make }} {{ $driver->vehicle->model }} ({{ $driver->vehicle->year }})
                    </h3>
                    <p class="text-sm text-gray-500">
                        Plate <span class="font-mono font-medium text-gray-900">{{ $driver->vehicle->license_plate }}</span>
                        · {{ ucfirst($driver->vehicle->vehicle_type) }}
                        · {{ $driver->vehicle->capacity }} seats
                        · {{ $driver->vehicle->color }}
                    </p>
                </div>
                <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium
                    {{ $driver->vehicle->request_status === 'approved' ? 'bg-green-100 text-green-800' : ($driver->vehicle->request_status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                    Vehicle {{ ucfirst($driver->vehicle->request_status) }}
                </span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach([
                    ['label' => 'Vehicle photo', 'url' => $driver->vehicle->vehicle_image_url],
                    ['label' => 'Registration paper', 'url' => $driver->vehicle->registration_image_url],
                    ['label' => 'Roadworthy certificate', 'url' => $driver->vehicle->roadworthy_image_url],
                ] as $doc)
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">{{ $doc['label'] }}</p>
                    @if($doc['url'])
                    <a href="{{ $doc['url'] }}" target="_blank" rel="noopener">
                        <img src="{{ $doc['url'] }}" alt="{{ $doc['label'] }}" class="h-32 w-full object-cover rounded-lg border border-gray-200">
                    </a>
                    @else
                    <p class="text-sm text-red-500">Not uploaded.</p>
                    @endif
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-gray-500">No vehicle submitted yet.</p>
            @endif
        </div>
    </div>
    @empty
    <div class="bg-white rounded-lg shadow p-6 text-center text-gray-500">No driver verification requests waiting.</div>
    @endforelse

    {{ $drivers->links() }}
</div>
@endsection