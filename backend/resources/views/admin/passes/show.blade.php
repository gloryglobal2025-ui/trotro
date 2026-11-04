@extends('admin.layout')

@section('title', 'QR Pass · '.$pass->code)

@push('styles')
<style>
    .pass-card {
        width: 85.6mm;
        height: 54mm;
        border-radius: 8px;
        overflow: hidden;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        display: flex;
        flex-direction: column;
        font-family: ui-sans-serif, system-ui, sans-serif;
    }

    .pass-card__head {
        background: linear-gradient(120deg, #ea580c, #f59e0b);
        color: #ffffff;
        padding: 3mm 4mm;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .pass-card__body {
        flex: 1;
        display: flex;
        align-items: center;
        gap: 3mm;
        padding: 3mm 3.5mm;
    }

    .pass-card__qr {
        width: 26mm;
        height: 26mm;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .pass-card__qr img,
    .pass-card__qr canvas {
        width: 26mm !important;
        height: 26mm !important;
    }

    @media print {
        @page {
            size: 85.6mm 54mm;
            margin: 0;
        }

        body * {
            visibility: hidden;
        }

        #print-card,
        #print-card * {
            visibility: visible;
        }

        #print-card {
            position: fixed;
            top: 0;
            left: 0;
            width: 85.6mm;
            height: 54mm;
            border: none;
            border-radius: 0;
            box-shadow: none;
        }
    }
</style>
@endpush

@section('content')
<div class="p-6 max-w-6xl">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <a href="{{ route('admin.passes.index') }}" class="text-sm text-orange-700 hover:underline">&larr; All passes</a>
            <h1 class="text-2xl font-bold text-gray-800 mt-1">{{ $pass->user->name }}</h1>
            <p class="text-sm text-gray-500">
                Pass code <span class="font-mono font-semibold text-gray-700">{{ $pass->code }}</span>
                <span class="ml-2 inline-flex px-2 py-0.5 rounded-full text-xs font-semibold {{ $pass->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ ucfirst($pass->status) }}
                </span>
            </p>
        </div>
        <button type="button" onclick="window.print()" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">
            Print card
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Printable card --}}
        <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
            <h2 class="font-semibold text-gray-800 mb-4">Card preview</h2>

            <div class="flex justify-center bg-gray-50 border border-dashed border-gray-300 rounded-lg py-10">
                <div id="print-card" class="pass-card">
                    <div class="pass-card__head">
                        <span class="text-sm font-bold tracking-wide">{{ \App\Helpers\SettingHelper::getAppName() }} PASS</span>
                        <span class="text-[10px] uppercase tracking-widest opacity-90">Tap &amp; ride</span>
                    </div>
                    <div class="pass-card__body">
                        <div id="pass-qr" class="pass-card__qr"></div>
                        <div class="min-w-0">
                            <div class="text-[12px] font-bold text-gray-900 leading-tight truncate">{{ $pass->user->name }}</div>
                            <div class="font-mono text-[10px] font-bold text-orange-700 mt-1 whitespace-nowrap">{{ $pass->code }}</div>
                            <div class="text-[8px] text-gray-500 mt-1 leading-snug">
                                Driver: scan this code, then choose the destination.
                            </div>
                            <div class="text-[8px] text-gray-400 mt-1">Keep this card safe</div>
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-xs text-gray-500 mt-4">
                If the QR will not scan, the driver can type the code above.
                @if($pass->requiresPin())
                    @if($pass->usesCustomerPhoneApproval())
                        This card is PIN protected with phone approval: the customer approves each fare on their own phone by SMS or USSD.
                    @else
                        This card is PIN protected: the customer taps their 4-digit PIN on the driver's phone before each fare.
                    @endif
                @else
                    Anyone holding this card can spend the wallet, so treat it like cash.
                @endif
            </p>
        </div>

        {{-- Wallet + actions --}}
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-sm text-gray-500">Wallet balance</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">
                    GHS {{ number_format((float) ($pass->user->wallet->balance ?? 0), 2) }}
                </p>
                <div class="grid grid-cols-2 gap-4 mt-4 text-sm">
                    <div>
                        <p class="text-gray-500">Trips</p>
                        <p class="font-semibold text-gray-900">{{ $pass->ride_count }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Last used</p>
                        <p class="font-semibold text-gray-900">{{ $pass->last_used_at?->format('d M, H:i') ?? 'Never' }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.passes.topup', $pass) }}" class="mt-5 flex gap-2">
                    @csrf
                    <input type="number" name="amount" min="1" max="10000" step="0.01" required
                           placeholder="Amount GHS"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                    <button type="submit" class="bg-green-600 text-white px-3 py-2 rounded text-sm hover:bg-green-700 whitespace-nowrap">
                        Load
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow p-6 space-y-3">
                <div class="flex items-center justify-between">
                    <p class="font-semibold text-gray-800">Customer approval</p>
                    @if($pass->requiresPin())
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">On</span>
                    @else
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">Off</span>
                    @endif
                </div>

                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-3 py-2 rounded">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if($pass->isPinLocked())
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-3 py-2 rounded">
                        Locked after too many wrong PINs.
                        @if($pass->pin_locked_until)
                            Try again after {{ $pass->pin_locked_until->format('H:i') }} or unlock now.
                        @endif
                    </div>
                    <form method="POST" action="{{ route('admin.passes.pin.unlock', $pass) }}">
                        @csrf
                        <button type="submit" class="w-full text-sm px-3 py-2 rounded bg-red-50 text-red-700 hover:bg-red-100">
                            Unlock PIN now
                        </button>
                    </form>
                @endif

                @if($pass->requiresPin())
                    <div class="rounded-lg border border-gray-200 p-3 text-xs text-gray-600 space-y-1">
                        <p class="font-semibold text-gray-800">
                            {{ $pass->usesCustomerPhoneApproval() ? 'Customer phone approval' : "Driver's phone approval" }}
                        </p>
                        @if($pass->usesCustomerPhoneApproval())
                            <p>The customer approves each fare by replying to an SMS or through USSD on their own phone.</p>
                            <p>
                                Phone:
                                <span class="font-semibold text-gray-800">{{ $pass->user->phone ?: 'not set' }}</span>
                            </p>
                        @else
                            <p>The customer taps their 4-digit PIN on the driver's phone before each fare.</p>
                        @endif
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.passes.pin', $pass) }}" class="space-y-2">
                    @csrf
                    <input type="password" name="pin" inputmode="numeric" pattern="[0-9]{4}" maxlength="4"
                           autocomplete="new-password" required
                           placeholder="{{ $pass->requiresPin() ? 'New 4-digit PIN' : 'Set a 4-digit PIN' }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm tracking-widest">
                    <input type="password" name="pin_confirmation" inputmode="numeric" pattern="[0-9]{4}" maxlength="4"
                           autocomplete="new-password" required placeholder="Repeat PIN"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm tracking-widest">
                    <button type="submit" class="w-full bg-orange-600 text-white px-3 py-2 rounded text-sm hover:bg-orange-700">
                        {{ $pass->requiresPin() ? 'Replace PIN' : 'Turn on approval' }}
                    </button>
                </form>

                @if($pass->requiresPin())
                    <form method="POST" action="{{ route('admin.passes.approval', $pass) }}" class="space-y-2 border-t border-gray-100 pt-3">
                        @csrf
                        <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Where does the customer approve?</p>
                        <label class="flex items-start gap-2 text-sm text-gray-700">
                            <input type="radio" name="approval_channel" value="device_pin" class="mt-0.5"
                                   {{ old('approval_channel', $pass->approval_channel) === 'device_pin' ? 'checked' : '' }}>
                            <span>Driver's phone PIN keypad</span>
                        </label>
                        <label class="flex items-start gap-2 text-sm text-gray-700">
                            <input type="radio" name="approval_channel" value="customer_phone" class="mt-0.5"
                                   {{ old('approval_channel', $pass->approval_channel) === 'customer_phone' ? 'checked' : '' }}>
                            <span>Customer's own phone (SMS / USSD)</span>
                        </label>
                        <button type="submit" class="w-full bg-gray-900 text-white px-3 py-2 rounded text-sm hover:bg-gray-800">
                            Save approval mode
                        </button>
                        <p class="text-xs text-gray-400">
                            Phone approval needs a valid phone number and an SMS provider configured on the server.
                        </p>
                    </form>

                    <form method="POST" action="{{ route('admin.passes.pin.remove', $pass) }}"
                          onsubmit="return confirm('Remove the PIN? The card can then be charged without the customer present.');">
                        @csrf
                        <button type="submit" class="w-full text-sm px-3 py-2 rounded bg-gray-100 text-gray-700 hover:bg-gray-200">
                            Remove PIN
                        </button>
                    </form>
                    @if($pass->pin_set_at)
                        <p class="text-xs text-gray-400">PIN set {{ $pass->pin_set_at->format('d M Y, H:i') }}</p>
                    @endif
                @endif
            </div>

            <div class="bg-white rounded-lg shadow p-6 space-y-4">
                <div>
                    <p class="font-semibold text-gray-800">Card status</p>
                    <form method="POST" action="{{ route('admin.passes.status', $pass) }}" class="mt-2">
                        @csrf
                        <input type="hidden" name="status" value="{{ $pass->status === 'active' ? 'suspended' : 'active' }}">
                        <button type="submit" class="text-sm px-3 py-2 rounded {{ $pass->status === 'active' ? 'bg-red-50 text-red-700 hover:bg-red-100' : 'bg-green-50 text-green-700 hover:bg-green-100' }}">
                            {{ $pass->status === 'active' ? 'Suspend card' : 'Reactivate card' }}
                        </button>
                    </form>
                </div>

                <div>
                    <p class="font-semibold text-gray-800">Replace lost card</p>
                    <form method="POST" action="{{ route('admin.passes.regenerate', $pass) }}" class="mt-2"
                          onsubmit="return confirm('Issue a new code? The old card will stop working and must be replaced.');">
                        @csrf
                        <button type="submit" class="text-sm px-3 py-2 rounded bg-gray-100 text-gray-700 hover:bg-gray-200">
                            Generate new code
                        </button>
                    </form>
                </div>

                @if($pass->notes)
                    <div>
                        <p class="font-semibold text-gray-800">Notes</p>
                        <p class="text-sm text-gray-600 mt-1">{{ $pass->notes }}</p>
                    </div>
                @endif

                <p class="text-xs text-gray-400">
                    Issued by {{ $pass->issuer?->name ?? 'admin' }} on {{ $pass->created_at?->format('d M Y') }}
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <h2 class="font-semibold text-gray-800 px-6 py-4 border-b">Recent trips</h2>
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase">Destination</th>
                        <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fare</th>
                        <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($pass->rides as $ride)
                        <tr>
                            <td class="px-6 py-3 text-gray-900">{{ $ride->destination_address ?? '—' }}</td>
                            <td class="px-6 py-3 text-gray-900">GHS {{ number_format((float) $ride->fare_amount, 2) }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ $ride->created_at?->format('d M, H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-gray-500">No trips yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <h2 class="font-semibold text-gray-800 px-6 py-4 border-b">Recent wallet activity</h2>
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                        <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($transactions as $transaction)
                        <tr>
                            <td class="px-6 py-3 text-gray-900">{{ $transaction->description }}</td>
                            <td class="px-6 py-3 font-medium {{ $transaction->amount < 0 ? 'text-red-600' : 'text-green-600' }}">
                                {{ $transaction->amount < 0 ? '-' : '+' }}GHS {{ number_format(abs((float) $transaction->amount), 2) }}
                            </td>
                            <td class="px-6 py-3 text-gray-500">GHS {{ number_format((float) $transaction->balance_after, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-gray-500">No wallet activity yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
    (function () {
        var target = document.getElementById('pass-qr');
        if (!target) {
            return;
        }

        if (window.QRCode) {
            new QRCode(target, {
                text: @json($pass->code),
                width: 160,
                height: 160,
                colorDark: '#111827',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M
            });
        } else {
            target.innerHTML = '<span class="font-mono font-bold text-[11px] text-gray-800 text-center">' + @json($pass->code) + '</span>';
        }
    })();
</script>
@endpush
