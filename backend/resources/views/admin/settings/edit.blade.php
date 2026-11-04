@extends('admin.layout')

@section('content')
<div class="p-4 sm:p-6 lg:p-8">
<div class="mb-6">
    <a href="{{ route('admin.settings.index') }}" class="text-orange-600 hover:text-orange-800 mb-4 inline-block">
        &larr; Back to Settings
    </a>
    <h1 class="text-2xl font-bold text-gray-800 mt-2">Edit Setting: {{ $setting->display_name }}</h1>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <form action="{{ route('admin.settings.update', $setting) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Key</label>
                <input type="text" value="{{ $setting->key }}" disabled class="w-full border rounded px-3 py-2 bg-gray-100">
                <p class="text-xs text-gray-500 mt-1">Key cannot be changed</p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Display Name</label>
                <input type="text" name="display_name" value="{{ $setting->display_name }}" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                <select name="type" required class="w-full border rounded px-3 py-2">
                    <option value="string" {{ $setting->type === 'string' ? 'selected' : '' }}>String</option>
                    <option value="integer" {{ $setting->type === 'integer' ? 'selected' : '' }}>Integer</option>
                    <option value="float" {{ $setting->type === 'float' ? 'selected' : '' }}>Float</option>
                    <option value="boolean" {{ $setting->type === 'boolean' ? 'selected' : '' }}>Boolean</option>
                    <option value="json" {{ $setting->type === 'json' ? 'selected' : '' }}>JSON</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Group</label>
                <select name="group" required class="w-full border rounded px-3 py-2">
                    <option value="general" {{ $setting->group === 'general' ? 'selected' : '' }}>General</option>
                    <option value="payment" {{ $setting->group === 'payment' ? 'selected' : '' }}>Payment</option>
                    <option value="ride" {{ $setting->group === 'ride' ? 'selected' : '' }}>Ride</option>
                    <option value="map" {{ $setting->group === 'map' ? 'selected' : '' }}>Map</option>
                    <option value="notification" {{ $setting->group === 'notification' ? 'selected' : '' }}>Notification</option>
                    <option value="fare" {{ $setting->group === 'fare' ? 'selected' : '' }}>Fare</option>
                    <option value="zone" {{ $setting->group === 'zone' ? 'selected' : '' }}>Zone</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Value</label>
                @if($setting->type === 'boolean')
                <select name="value" class="w-full border rounded px-3 py-2">
                    <option value="1" {{ $setting->value === '1' || $setting->value === 'true' ? 'selected' : '' }}>Yes</option>
                    <option value="0" {{ $setting->value === '0' || $setting->value === 'false' ? 'selected' : '' }}>No</option>
                </select>
                @elseif($setting->type === 'integer')
                <input type="number" name="value" value="{{ $setting->value }}" class="w-full border rounded px-3 py-2">
                @elseif($setting->type === 'float')
                <input type="number" step="0.01" name="value" value="{{ $setting->value }}" class="w-full border rounded px-3 py-2">
                @elseif($setting->type === 'json')
                <textarea name="value" rows="3" class="w-full border rounded px-3 py-2 font-mono text-sm">{{ $setting->value }}</textarea>
                @else
                <input type="text" name="value" value="{{ $setting->value }}" class="w-full border rounded px-3 py-2">
                @endif
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" rows="3" class="w-full border rounded px-3 py-2">{{ $setting->description }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Public</label>
                <select name="is_public" class="w-full border rounded px-3 py-2">
                    <option value="0" {{ !$setting->is_public ? 'selected' : '' }}>No - Admin only</option>
                    <option value="1" {{ $setting->is_public ? 'selected' : '' }}>Yes - Accessible via API</option>
                </select>
            </div>
        </div>
        <div class="mt-6">
            <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">
                Update Setting
            </button>
        </div>
    </form>
</div>
</div>
@endsection
