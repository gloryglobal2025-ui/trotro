@extends('admin.layout')

@section('title', 'Vehicle Management')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Vehicle Management</h1>
        <a href="{{ route('admin.vehicles.create') }}" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">
            + Add Vehicle
        </a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Driver</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vehicle</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">License Plate</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Capacity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Request Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($vehicles as $vehicle)
                <tr>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $vehicle->driver->name }}</div>
                        <div class="text-sm text-gray-500">{{ $vehicle->driver->email }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $vehicle->make }} {{ $vehicle->model }}</div>
                        <div class="text-sm text-gray-500">{{ $vehicle->year }} - {{ $vehicle->color }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900 font-mono">{{ $vehicle->license_plate }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ ucfirst($vehicle->vehicle_type) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $vehicle->capacity }} seats</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-xs {{ $vehicle->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $vehicle->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-xs
                            {{ $vehicle->request_status === 'approved' ? 'bg-green-100 text-green-800' : ($vehicle->request_status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                            {{ ucfirst($vehicle->request_status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium">
                        <a href="{{ route('admin.vehicles.edit', $vehicle) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                        <form action="{{ route('admin.vehicles.destroy', $vehicle) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">No vehicles found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $vehicles->links() }}

    <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="{{ route('admin.vehicles.attribute-setup') }}" class="bg-white p-4 rounded-lg shadow hover:shadow-md">
            <h3 class="font-semibold text-gray-800">Vehicle Attribute Setup</h3>
            <p class="text-sm text-gray-500 mt-1">Manage brands, colors, and vehicle attributes</p>
        </a>
        <a href="{{ route('admin.vehicles.new-requests') }}" class="bg-white p-4 rounded-lg shadow hover:shadow-md">
            <h3 class="font-semibold text-gray-800">New Vehicle Requests</h3>
            <p class="text-sm text-gray-500 mt-1">Review and approve new vehicle registrations</p>
        </a>
        <a href="{{ route('admin.vehicles.update-requests') }}" class="bg-white p-4 rounded-lg shadow hover:shadow-md">
            <h3 class="font-semibold text-gray-800">Update Vehicle Requests</h3>
            <p class="text-sm text-gray-500 mt-1">Manage vehicle update requests</p>
        </a>
    </div>
</div>
@endsection
