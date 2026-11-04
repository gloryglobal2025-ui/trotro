@extends('admin.layout')

@section('content')
<div class="p-4 sm:p-6 lg:p-8">
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Rides Management</h1>
    <p class="text-gray-600">View and manage all ride requests</p>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Passenger</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Driver</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Route</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fare</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($rides as $ride)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#{{ $ride->id }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $ride->passenger->name ?? 'N/A' }}
                        <div class="text-xs text-gray-500">{{ $ride->passenger->phone ?? '' }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $ride->driver->name ?? 'Unassigned' }}
                        @if($ride->driver)
                        <div class="text-xs text-gray-500">{{ $ride->driver->phone ?? '' }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        <div class="text-xs text-gray-500">From: {{ $ride->origin_address }}</div>
                        <div class="text-xs text-gray-500">To: {{ $ride->destination_address }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'accepted' => 'bg-blue-100 text-blue-800',
                                'driver_arrived' => 'bg-purple-100 text-purple-800',
                                'in_progress' => 'bg-indigo-100 text-indigo-800',
                                'completed' => 'bg-green-100 text-green-800',
                                'cancelled' => 'bg-red-100 text-red-800',
                            ];
                            $statusClass = $statusColors[$ride->status] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClass }}">
                            {{ ucfirst(str_replace('_', ' ', $ride->status)) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        @if($ride->fare_amount)
                            GHS {{ number_format($ride->fare_amount, 2) }}
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $ride->created_at->format('M d, Y H:i') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="{{ route('admin.rides.show', $ride) }}" class="text-orange-600 hover:text-orange-900 mr-3">View</a>
                        @if(in_array($ride->status, ['accepted', 'driver_arrived', 'in_progress']))
                        <a href="{{ route('admin.rides.tracking', $ride) }}" class="text-blue-600 hover:text-blue-900">Track</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                        No rides found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $rides->links() }}
</div>
@endsection
