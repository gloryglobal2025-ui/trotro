@extends('admin.layout')

@section('content')
<div class="p-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold">Bus Stops</h2>
        <a href="{{ route('admin.bus-stops.create') }}" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">
            Add New Bus Stop
        </a>
    </div>

    <div class="bg-white rounded-lg shadow">
        <table class="w-full">
            <thead>
                <tr class="text-left border-b bg-gray-50">
                    <th class="p-4">ID</th>
                    <th class="p-4">Name</th>
                    <th class="p-4">Photo</th>
                    <th class="p-4">Location</th>
                    <th class="p-4">Area</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($busStops as $busStop)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-4">{{ $busStop->id }}</td>
                        <td class="p-4 font-semibold">{{ $busStop->name }}</td>
                        <td class="p-4">
                            @if($busStop->image_url)
                                <img src="{{ $busStop->image_url }}" alt="{{ $busStop->name }}" class="h-12 w-16 rounded-lg border object-cover">
                            @else
                                <span class="text-xs text-gray-400">No photo</span>
                            @endif
                        </td>
                        <td class="p-4">{{ $busStop->location ?? '-' }}</td>
                        <td class="p-4">{{ $busStop->area ?? '-' }}</td>
                        <td class="p-4">
                            <a href="{{ route('admin.bus-stops.show', $busStop) }}" class="text-blue-600 hover:underline mr-2">View</a>
                            <a href="{{ route('admin.bus-stops.edit', $busStop) }}" class="text-green-600 hover:underline mr-2">Edit</a>
                            <form action="{{ route('admin.bus-stops.destroy', $busStop) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $busStops->links() }}
    </div>
</div>
@endsection
