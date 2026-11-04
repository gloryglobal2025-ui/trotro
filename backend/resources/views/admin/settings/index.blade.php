@extends('admin.layout')

@section('content')
<div class="p-4 sm:p-6 lg:p-8">
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">System Settings</h1>
        <p class="text-gray-600">Manage application configuration</p>
    </div>
    <a href="{{ route('admin.settings.create') }}" class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition">
        + Add Setting
    </a>
</div>

<div class="bg-white rounded-lg shadow">
    <div class="border-b">
        <nav class="flex space-x-8 px-6" id="tabs">
            <button onclick="showGroup('all')" class="tab-btn py-4 px-1 border-b-2 border-orange-600 text-orange-600 font-medium" data-group="all">
                All
            </button>
            @foreach($groups as $group)
            <button onclick="showGroup('{{ $group }}')" class="tab-btn py-4 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium" data-group="{{ $group }}">
                {{ ucfirst($group) }}
            </button>
            @endforeach
        </nav>
    </div>

    <form action="{{ route('admin.settings.bulkUpdate') }}" method="POST" id="settings-form">
        @csrf
        <div class="p-6">
            @foreach($settings->groupBy('group') as $group => $groupSettings)
            <div class="setting-group mb-8" data-group="{{ $group }}">
                <h3 class="text-lg font-bold text-gray-800 mb-4">{{ ucfirst($group) }} Settings</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($groupSettings as $setting)
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            {{ $setting->display_name }}
                            @if($setting->is_public)
                            <span class="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">Public</span>
                            @endif
                        </label>
                        @if($setting->description)
                        <p class="text-xs text-gray-500 mb-2">{{ $setting->description }}</p>
                        @endif

                        @if($setting->type === 'boolean')
                        <select name="settings[{{ $setting->key }}]" class="w-full border rounded px-3 py-2">
                            <option value="1" {{ $setting->value === '1' || $setting->value === 'true' ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ $setting->value === '0' || $setting->value === 'false' ? 'selected' : '' }}>No</option>
                        </select>
                        @elseif($setting->type === 'integer')
                        <input type="number" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}" class="w-full border rounded px-3 py-2">
                        @elseif($setting->type === 'float')
                        <input type="number" step="0.01" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}" class="w-full border rounded px-3 py-2">
                        @elseif($setting->type === 'json')
                        <textarea name="settings[{{ $setting->key }}]" rows="3" class="w-full border rounded px-3 py-2 font-mono text-sm">{{ $setting->value }}</textarea>
                        @else
                        <input type="text" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}" class="w-full border rounded px-3 py-2">
                        @endif

                        <div class="mt-2 flex justify-between items-center">
                            <span class="text-xs text-gray-400">Key: {{ $setting->key }}</span>
                            <a href="{{ route('admin.settings.edit', $setting) }}" class="text-blue-600 hover:text-blue-800 text-sm">Edit</a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

        <div class="bg-gray-50 px-6 py-4 border-t">
            <button type="submit" class="bg-orange-600 text-white px-6 py-2 rounded hover:bg-orange-700 w-full md:w-auto">
                Save All Settings
            </button>
        </div>
    </form>
</div>

</div>

@push('scripts')
<script>
    function showGroup(group) {
        // Update tab styles
        document.querySelectorAll('.tab-btn').forEach(btn => {
            if (btn.dataset.group === group) {
                btn.classList.remove('border-transparent', 'text-gray-500');
                btn.classList.add('border-orange-600', 'text-orange-600');
            } else {
                btn.classList.remove('border-orange-600', 'text-orange-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            }
        });

        // Show/hide groups
        document.querySelectorAll('.setting-group').forEach(el => {
            if (group === 'all' || el.dataset.group === group) {
                el.style.display = 'block';
            } else {
                el.style.display = 'none';
            }
        });
    }
</script>
@endpush
@endsection
