@extends('admin.layout')

@section('title', 'Withdrawals')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Withdrawal Requests</h1>
        <a href="{{ route('admin.users.index') }}" class="text-orange-600 hover:text-orange-800">← Back to Users</a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account Details</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requested</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($withdrawals as $withdrawal)
                <tr>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $withdrawal->user->name }}</div>
                        <div class="text-sm text-gray-500">{{ $withdrawal->user->email }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm font-semibold text-gray-900">GHS {{ number_format($withdrawal->amount, 2) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ ucfirst($withdrawal->method) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        @if($withdrawal->method === 'bank')
                            {{ $withdrawal->bank_name }} - {{ $withdrawal->account_number }}
                        @elseif($withdrawal->method === 'momo')
                            {{ $withdrawal->mobile_number }}
                        @else
                            {{ $withdrawal->account_number ?? 'N/A' }}
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-xs
                            {{ $withdrawal->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($withdrawal->status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') }}">
                            {{ ucfirst($withdrawal->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $withdrawal->created_at->format('M d, Y') }}</td>
                    <td class="px-6 py-4 text-sm font-medium">
                        @if($withdrawal->status === 'pending')
                            <form action="{{ route('admin.users.withdrawals.approve', $withdrawal) }}" method="POST" class="inline mr-2">
                                @csrf
                                <button type="submit" class="text-green-600 hover:text-green-900" onclick="return confirm('Approve this withdrawal?')">Approve</button>
                            </form>
                            <button onclick="showRejectModal({{ $withdrawal->id }})" class="text-red-600 hover:text-red-900">Reject</button>
                        @else
                            <span class="text-gray-400">Processed</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">No withdrawal requests</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $withdrawals->links() }}
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold mb-4">Reject Withdrawal</h3>
        <form action="" method="POST" id="rejectForm">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Reason (Optional)</label>
                <textarea name="notes" rows="3" class="w-full px-3 py-2 border rounded-lg"></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeRejectModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Reject</button>
            </div>
        </form>
    </div>
</div>

<script>
function showRejectModal(id) {
    const modal = document.getElementById('rejectModal');
    const form = document.getElementById('rejectForm');
    form.action = '/admin/users/withdrawals/' + id + '/reject';
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeRejectModal() {
    const modal = document.getElementById('rejectModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>
@endsection
