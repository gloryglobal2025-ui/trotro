@extends('admin.layout')

@section('title', 'Customer Wallets')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Customer Wallets</h1>
        <div class="space-x-4">
            <a href="{{ route('admin.users.deposits') }}" class="text-orange-600 hover:text-orange-800">Deposits</a>
            <a href="{{ route('admin.users.withdrawals') }}" class="text-orange-600 hover:text-orange-800">Withdrawals</a>
            <a href="{{ route('admin.users.wallet-bonuses') }}" class="text-orange-600 hover:text-orange-800">Bonuses</a>
            <a href="{{ route('admin.users.index') }}" class="text-orange-600 hover:text-orange-800">← Back to Users</a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Balance</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bonus Balance</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pending</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Available</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Transaction</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($wallets as $wallet)
                <tr>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $wallet->user->name }}</div>
                        <div class="text-sm text-gray-500">{{ $wallet->user->email }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm font-semibold text-gray-900">GHS {{ number_format($wallet->balance, 2) }}</td>
                    <td class="px-6 py-4 text-sm text-green-600">GHS {{ number_format($wallet->bonus_balance, 2) }}</td>
                    <td class="px-6 py-4 text-sm text-orange-600">GHS {{ number_format($wallet->pending_balance, 2) }}</td>
                    <td class="px-6 py-4 text-sm font-bold text-gray-900">GHS {{ number_format($wallet->available_balance, 2) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ $wallet->last_transaction_at ? $wallet->last_transaction_at->format('M d, Y H:i') : 'Never' }}
                    </td>
                    <td class="px-6 py-4 text-sm font-medium">
                        <button onclick="showEditModal({{ $wallet->id }}, {{ $wallet->balance }}, {{ $wallet->bonus_balance }})" class="text-indigo-600 hover:text-indigo-900">Edit</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">No wallets found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $wallets->links() }}
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold mb-4">Edit Wallet Balance</h3>
        <form action="" method="POST" id="editForm">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Balance</label>
                <input type="number" name="balance" id="balance" step="0.01" class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Bonus Balance</label>
                <input type="number" name="bonus_balance" id="bonus_balance" step="0.01" class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
function showEditModal(id, balance, bonusBalance) {
    const modal = document.getElementById('editModal');
    const form = document.getElementById('editForm');
    document.getElementById('balance').value = balance;
    document.getElementById('bonus_balance').value = bonusBalance;
    form.action = '/admin/users/customer-wallets/' + id;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeEditModal() {
    const modal = document.getElementById('editModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>
@endsection
