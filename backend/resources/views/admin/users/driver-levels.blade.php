@extends('admin.layout')

@section('title', 'Driver Levels')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Driver Levels</h1>
        <a href="{{ route('admin.users.index') }}" class="text-orange-600 hover:text-orange-800">← Back to Users</a>
    </div>

    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <h2 class="text-lg font-semibold mb-4">Add New Driver Level</h2>
        <form action="{{ route('admin.users.storeDriverLevel') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Slug</label>
                    <input type="text" name="slug" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Commission Rate (%)</label>
                    <input type="number" name="commission_rate" step="0.01" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fare Discount (%)</label>
                    <input type="number" name="fare_discount" step="0.01" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Daily Rides Limit</label>
                    <input type="number" name="daily_rides_limit" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Min Rating</label>
                    <input type="number" name="min_rating" step="0.1" min="1" max="5" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Order</label>
                    <input type="number" name="order" required class="w-full px-3 py-2 border rounded-lg">
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700">
                    Add Level
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Commission</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Discount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rides Limit</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Min Rating</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($levels as $level)
                <tr>
                    <td class="px-6 py-4">{{ $level->name }}</td>
                    <td class="px-6 py-4">{{ $level->slug }}</td>
                    <td class="px-6 py-4">{{ $level->commission_rate }}%</td>
                    <td class="px-6 py-4">{{ $level->fare_discount }}%</td>
                    <td class="px-6 py-4">{{ $level->daily_rides_limit ?? 'Unlimited' }}</td>
                    <td class="px-6 py-4">⭐ {{ $level->min_rating }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-xs {{ $level->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $level->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">No driver levels configured</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
