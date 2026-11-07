@extends('admin.layout')

@section('title', 'User Management')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">User Management</h1>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.users.verification-requests') }}" class="relative bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-50">
                Driver Verification
                @if(($pendingVerifications ?? 0) > 0)
                <span class="ml-2 inline-flex items-center justify-center min-w-[1.5rem] h-6 px-2 rounded-full bg-red-600 text-white text-xs font-bold">{{ $pendingVerifications }}</span>
                @endif
            </a>
            <a href="{{ route('admin.users.create') }}" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">
                + Add User
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Level</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rides</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($users as $user)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="h-10 w-10 rounded-full bg-orange-100 flex items-center justify-center overflow-hidden">
                                @if($user->profile_image_url)
                                    <img src="{{ $user->profile_image_url }}" alt="{{ $user->name }}" class="h-10 w-10 object-cover">
                                @else
                                    <span class="text-orange-600 font-bold">{{ substr($user->name, 0, 1) }}</span>
                                @endif
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                <div class="text-sm text-gray-500">{{ $user->phone }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $user->email }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                            {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-800' : ($user->role === 'driver' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800') }}">
                            {{ ucfirst($user->role) }}
                        </span>
                        @if($user->role === 'driver')
                            @php
                                $verificationStyles = [
                                    'approved' => 'bg-green-100 text-green-800',
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'rejected' => 'bg-red-100 text-red-800',
                                    'unverified' => 'bg-gray-100 text-gray-600',
                                ];
                                $verificationStatus = $user->verification_status ?? 'unverified';
                            @endphp
                            <span class="mt-1 block px-2 py-0.5 w-max text-xs font-semibold rounded-full {{ $verificationStyles[$verificationStatus] ?? $verificationStyles['unverified'] }}">
                                {{ ucfirst($verificationStatus) }}
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $user->userLevel?->name ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ⭐ {{ $user->rating ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $user->total_rides ?? 0 }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="{{ route('admin.users.edit', $user) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">No users found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $users->links() }}

    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('admin.users.verification-requests') }}" class="bg-white p-4 rounded-lg shadow hover:shadow-md">
            <h3 class="font-semibold text-gray-800">Driver Verification</h3>
            <p class="text-sm text-gray-500 mt-1">Review licences, Ghana Cards and vehicle papers</p>
        </a>
        <a href="{{ route('admin.users.driver-levels') }}" class="bg-white p-4 rounded-lg shadow hover:shadow-md">
            <h3 class="font-semibold text-gray-800">Driver Levels</h3>
            <p class="text-sm text-gray-500 mt-1">Manage driver tiers and commissions</p>
        </a>
        <a href="{{ route('admin.users.customer-levels') }}" class="bg-white p-4 rounded-lg shadow hover:shadow-md">
            <h3 class="font-semibold text-gray-800">Customer Levels</h3>
            <p class="text-sm text-gray-500 mt-1">Manage customer tiers and discounts</p>
        </a>
        <a href="{{ route('admin.users.deposits') }}" class="bg-white p-4 rounded-lg shadow hover:shadow-md">
            <h3 class="font-semibold text-gray-800">Wallet Deposits</h3>
            <p class="text-sm text-gray-500 mt-1">Approve top-up requests</p>
        </a>
        <a href="{{ route('admin.users.withdrawals') }}" class="bg-white p-4 rounded-lg shadow hover:shadow-md">
            <h3 class="font-semibold text-gray-800">Withdrawals</h3>
            <p class="text-sm text-gray-500 mt-1">Process driver withdrawal requests</p>
        </a>
        <a href="{{ route('admin.users.customer-wallets') }}" class="bg-white p-4 rounded-lg shadow hover:shadow-md">
            <h3 class="font-semibold text-gray-800">Customer Wallets</h3>
            <p class="text-sm text-gray-500 mt-1">Manage customer wallet balances</p>
        </a>
        <a href="{{ route('admin.users.wallet-bonuses') }}" class="bg-white p-4 rounded-lg shadow hover:shadow-md">
            <h3 class="font-semibold text-gray-800">Wallet Bonuses</h3>
            <p class="text-sm text-gray-500 mt-1">Create promotional bonuses</p>
        </a>
    </div>
</div>
@endsection
