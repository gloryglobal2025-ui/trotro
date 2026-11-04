@extends('admin.layout')

@section('content')
<div class="p-4 sm:p-6 lg:p-8">
<div class="mb-6">
    <a href="{{ route('admin.settings.index') }}" class="text-orange-600 hover:text-orange-800 mb-4 inline-block">
        &larr; Back to Settings
    </a>
    <h1 class="text-2xl font-bold text-gray-800 mt-2">Create Setting</h1>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <form action="{{ route('admin.settings.store') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Key</label>
                <input type="text" name="key" required class="w-full border rounded px-3 py-2" placeholder="e.g., app_name">
                <p class="text-xs text-gray-500 mt-1">Unique identifier for the setting (use snake_case)</p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Display Name</label>
                <input type="text" name="display_name" required class="w-full border rounded px-3 py-2" placeholder="e.g., Application Name">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                <select name="type" required class="w-full border rounded px-3 py-2">
                    <option value="string">String</option>
                    <option value="integer">Integer</option>
                    <option value="float">Float</option>
                    <option value="boolean">Boolean</option>
                    <option value="json">JSON</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Group</label>
                <select name="group" required class="w-full border rounded px-3 py-2">
                    <option value="general">General</option>
                    <option value="payment">Payment</option>
                    <option value="ride">Ride</option>
                    <option value="map">Map</option>
                    <option value="notification">Notification</option>
                    <option value="fare">Fare</option>
                    <option value="zone">Zone</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Value</label>
                <input type="text" name="value" class="w-full border rounded px-3 py-2" placeholder="e.g., Trotro App">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" rows="3" class="w-full border rounded px-3 py-2" placeholder="Description of what this setting does"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Public</label>
                <select name="is_public" class="w-full border rounded px-3 py-2">
                    <option value="0">No - Admin only</option>
                    <option value="1">Yes - Accessible via API</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">Whether this setting should be accessible via public API</p>
            </div>
        </div>
        <div class="mt-6">
            <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">
                Create Setting
            </button>
        </div>
    </form>
</div>
</div>
@endsection
