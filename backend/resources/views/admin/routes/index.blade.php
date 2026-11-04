@extends('admin.layout')

@section('content')
<div class="p-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold">Routes</h2>
        <a href="{{ route('admin.routes.create') }}" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">
            Add New Route
        </a>
    </div>

    <div class="bg-white rounded-lg shadow">
        <table class="w-full">
            <thead>
                <tr class="text-left border-b bg-gray-50">
                    <th class="p-4">ID</th>
                    <th class="p-4">Route Name</th>
                    <th class="p-4">Origin</th>
                    <th class="p-4">Destination</th>
                    <th class="p-4">Duration</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($routes as $route)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-4">{{ $route->id }}</td>
                        <td class="p-4 font-semibold">{{ $route->route_name }}</td>
                        <td class="p-4">{{ $route->origin->name }}</td>
                        <td class="p-4">{{ $route->destination->name }}</td>
                        <td class="p-4">{{ $route->estimated_duration ?? '-' }} min</td>
                        <td class="p-4">
                            <a href="{{ route('admin.routes.show', $route) }}" class="text-blue-600 hover:underline mr-2">View</a>
                            <a href="{{ route('admin.routes.edit', $route) }}" class="text-green-600 hover:underline mr-2">Edit</a>
                            <a href="{{ route('admin.routes.stops', $route) }}" class="text-orange-600 hover:underline mr-2">Stops</a>
                            <form action="{{ route('admin.routes.destroy', $route) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $routes->links() }}
    </div>
</div>
@endsection
