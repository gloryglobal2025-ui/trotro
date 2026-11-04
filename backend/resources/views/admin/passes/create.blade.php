@extends('admin.layout')

@section('title', 'Register Pass Customer')

@section('content')
<div class="p-6 max-w-3xl">
    <div class="mb-6">
        <a href="{{ route('admin.passes.index') }}" class="text-sm text-orange-600 hover:underline">&larr; Back to passes</a>
        <h1 class="text-2xl font-bold text-gray-800 mt-2">Register a pass customer</h1>
        <p class="text-sm text-gray-500 mt-1">
            This creates a passenger account with a prepaid wallet and a printable QR card.
            No email or password is needed from the customer.
        </p>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-5">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.passes.store') }}" method="POST" class="bg-white rounded-lg shadow p-6 space-y-5">
        @csrf

        <div>
            <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">Full name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required
                   class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400"
                   placeholder="e.g. Auntie Akosua">
        </div>

        <div>
            <label for="phone" class="block text-sm font-semibold text-gray-700 mb-1">Phone number <span class="text-gray-400 font-normal">(required for phone approval)</span></label>
            <input id="phone" name="phone" type="text" value="{{ old('phone') }}"
                   class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400"
                   placeholder="024 000 0000">
        </div>

        <div>
            <label for="initial_balance" class="block text-sm font-semibold text-gray-700 mb-1">Money to load now (GHS)</label>
            <input id="initial_balance" name="initial_balance" type="number" min="0" max="10000" step="0.01"
                   value="{{ old('initial_balance', '0') }}"
                   class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400">
            <p class="text-xs text-gray-500 mt-1">You can top up again later from the card page.</p>
        </div>

        <div>
            <label for="notes" class="block text-sm font-semibold text-gray-700 mb-1">Notes <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="notes" name="notes" rows="3"
                      class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400"
                      placeholder="e.g. Visually impaired, travels with a guide">{{ old('notes') }}</textarea>
        </div>

        <div class="border border-gray-200 rounded-lg p-4 space-y-3">
            <div>
                <p class="text-sm font-semibold text-gray-700">Customer approval <span class="text-gray-400 font-normal">(optional)</span></p>
                <p class="text-xs text-gray-500 mt-1">
                    Set a 4-digit PIN if the customer should approve every trip. Choose where they approve it.
                    Leave the PIN boxes empty to let the card work without approval.
                </p>
            </div>

            <div class="space-y-2">
                <label class="flex gap-3 items-start border border-gray-300 rounded-lg p-3 cursor-pointer hover:border-orange-400">
                    <input type="radio" name="approval_channel" value="device_pin" class="mt-1"
                           {{ old('approval_channel', 'device_pin') === 'device_pin' ? 'checked' : '' }}>
                    <span>
                        <span class="block text-sm font-semibold text-gray-800">Driver's phone PIN keypad</span>
                        <span class="block text-xs text-gray-500">The driver hands the phone over and the customer taps their PIN.</span>
                    </span>
                </label>
                <label class="flex gap-3 items-start border border-gray-300 rounded-lg p-3 cursor-pointer hover:border-orange-400">
                    <input type="radio" name="approval_channel" value="customer_phone" class="mt-1"
                           {{ old('approval_channel') === 'customer_phone' ? 'checked' : '' }}>
                    <span>
                        <span class="block text-sm font-semibold text-gray-800">Customer's own phone (SMS / USSD)</span>
                        <span class="block text-xs text-gray-500">The customer gets a text and replies with their PIN. Requires the phone number above.</span>
                    </span>
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label for="pin" class="block text-sm font-semibold text-gray-700 mb-1">PIN (4 numbers)</label>
                    <input id="pin" name="pin" type="password" inputmode="numeric" pattern="[0-9]{4}" maxlength="4"
                           autocomplete="new-password"
                           class="w-full border border-gray-300 rounded px-4 py-2 tracking-widest focus:outline-none focus:ring-2 focus:ring-orange-400"
                           placeholder="••••">
                </div>
                <div>
                    <label for="pin_confirmation" class="block text-sm font-semibold text-gray-700 mb-1">Repeat PIN</label>
                    <input id="pin_confirmation" name="pin_confirmation" type="password" inputmode="numeric" pattern="[0-9]{4}" maxlength="4"
                           autocomplete="new-password"
                           class="w-full border border-gray-300 rounded px-4 py-2 tracking-widest focus:outline-none focus:ring-2 focus:ring-orange-400"
                           placeholder="••••">
                </div>
            </div>
            <p class="text-xs text-gray-500">
                Phone approval also needs an SMS provider configured on the server. Without one, keep the driver keypad option.
            </p>
        </div>

        <div class="bg-orange-50 border border-orange-200 rounded p-4 text-sm text-orange-800">
            After saving you will get a printable card. The customer only needs to show that card to a driver.
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-orange-600 text-white px-6 py-2 rounded hover:bg-orange-700 font-semibold">
                Create pass &amp; load wallet
            </button>
            <a href="{{ route('admin.passes.index') }}" class="px-6 py-2 rounded border border-gray-300 text-gray-700 hover:bg-gray-50">Cancel</a>
        </div>
    </form>
</div>
@endsection
