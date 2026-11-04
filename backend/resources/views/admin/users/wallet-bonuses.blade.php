@extends('admin.layout')

@section('title', 'Wallet Bonuses')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Wallet Bonuses</h1>
        <div class="space-x-4">
            <a href="{{ route('admin.users.customer-wallets') }}" class="text-orange-600 hover:text-orange-800">Wallets</a>
            <a href="{{ route('admin.users.index') }}" class="text-orange-600 hover:text-orange-800">← Back to Users</a>
        </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <h2 class="text-lg font-semibold mb-4">Add New Wallet Bonus</h2>
        <form action="{{ route('admin.users.wallet-bonuses.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Code</label>
                    <input type="text" name="code" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Amount</label>
                    <input type="number" name="amount" step="0.01" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                    <select name="type" required class="w-full px-3 py-2 border rounded-lg">
                        <option value="signup">Signup Bonus</option>
                        <option value="referral">Referral Bonus</option>
                        <option value="promotional">Promotional</option>
                        <option value="special">Special</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Min Deposit</label>
                    <input type="number" name="min_deposit" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Min Wallet Balance</label>
                    <input type="number" name="min_wallet_balance" step="0.01" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Usage Limit</label>
                    <input type="number" name="usage_limit" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Valid From</label>
                    <input type="date" name="valid_from" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Valid Until</label>
                    <input type="date" name="valid_until" class="w-full px-3 py-2 border rounded-lg">
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" rows="2" class="w-full px-3 py-2 border rounded-lg"></textarea>
            </div>
            <div class="mt-4">
                <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700">
                    Add Bonus
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <h2 class="text-lg font-semibold mb-4">Grant Bonus to User</h2>
        <form action="{{ route('admin.users.wallet-bonuses.grant') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Bonus</label>
                <select name="wallet_bonus_id" required class="w-full px-3 py-2 border rounded-lg">
                    @foreach($bonuses as $bonus)
                        <option value="{{ $bonus->id }}">{{ $bonus->name }} (GHS {{ number_format($bonus->amount, 2) }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">User</label>
                <select name="user_id" required class="w-full px-3 py-2 border rounded-lg">
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                    Grant Bonus
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usage</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valid Period</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($bonuses as $bonus)
                <tr>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $bonus->name }}</div>
                        <div class="text-sm text-gray-500">{{ $bonus->description }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900 font-mono">{{ $bonus->code }}</td>
                    <td class="px-6 py-4 text-sm font-semibold text-green-600">GHS {{ number_format($bonus->amount, 2) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ ucfirst($bonus->type) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ $bonus->used_count }}{{ $bonus->usage_limit ? '/' . $bonus->usage_limit : '' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ $bonus->valid_from?->format('M d, Y') ?? 'N/A' }} -
                        {{ $bonus->valid_until?->format('M d, Y') ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-xs {{ $bonus->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $bonus->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">No wallet bonuses configured</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $bonuses->links() }}
</div>
@endsection
