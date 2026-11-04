@extends('admin.layout')

@section('content')
<div class="p-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold">Fares</h2>
        <a href="{{ route('admin.fares.create') }}" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">
            Add New Fare
        </a>
    </div>

    <div class="bg-white rounded-lg shadow">
        <table class="w-full">
            <thead>
                <tr class="text-left border-b bg-gray-50">
                    <th class="p-4">ID</th>
                    <th class="p-4">Route</th>
                    <th class="p-4">Fare Type</th>
                    <th class="p-4">Amount</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($fares as $fare)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-4">{{ $fare->id }}</td>
                        <td class="p-4">{{ $fare->route->route_name }}</td>
                        <td class="p-4">{{ $fare->fare_type }}</td>
                        <td class="p-4 font-semibold">GHS {{ number_format($fare->amount, 2) }}</td>
                        <td class="p-4">
                            @if($fare->is_active)
                                <span class="bg-green-100 text-green-800 px-2 py-1 rounded">Active</span>
                            @else
                                <span class="bg-red-100 text-red-800 px-2 py-1 rounded">Inactive</span>
                            @endif
                        </td>
                        <td class="p-4">
                            <a href="{{ route('admin.fares.show', $fare) }}" class="text-blue-600 hover:underline mr-2">View</a>
                            <a href="{{ route('admin.fares.edit', $fare) }}" class="text-green-600 hover:underline mr-2">Edit</a>
                            <form action="{{ route('admin.fares.destroy', $fare) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $fares->links() }}
    </div>
</div>
@endsection
