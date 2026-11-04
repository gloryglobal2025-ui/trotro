@extends('admin.layout')

@section('title', 'QR Passes')

@section('content')
<div class="p-6">
    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">QR Passes</h1>
            <p class="text-sm text-gray-500 mt-1">Prepaid cards for customers who need help using the app.</p>
        </div>
        <a href="{{ route('admin.passes.create') }}" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700 font-semibold">
            + Register customer
        </a>
    </div>

    <form method="GET" class="mb-5">
        <div class="flex gap-2 max-w-xl">
            <input type="text" name="q" value="{{ $search }}" placeholder="Search by name, phone or pass code"
                   class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400">
            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-900">Search</button>
        </div>
    </form>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pass code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trips</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($passes as $pass)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="h-10 w-10 rounded-full bg-orange-100 flex items-center justify-center">
                                <span class="text-orange-600 font-bold">{{ substr($pass->user->name ?? '?', 0, 1) }}</span>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">{{ $pass->user->name ?? 'Unknown' }}</div>
                                <div class="text-sm text-gray-500">{{ $pass->user->phone ?? 'No phone' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="font-mono text-sm bg-gray-100 px-2 py-1 rounded">{{ $pass->code }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                        GHS {{ number_format($pass->user?->wallet?->available_balance ?? 0, 2) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $pass->ride_count }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $pass->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ ucfirst($pass->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="{{ route('admin.passes.show', $pass) }}" class="text-indigo-600 hover:text-indigo-900">Open card</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        No passes yet. Register a customer to print their first card.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $passes->links() }}</div>
</div>
@endsection
