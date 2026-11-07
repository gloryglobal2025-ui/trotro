@extends('admin.layout')

@section('title', 'Vehicle Attribute Setup')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <a href="{{ route('admin.vehicles.index') }}" class="text-orange-600 hover:text-orange-800">← Back to Vehicles</a>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-6">Vehicle Attribute Setup</h1>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Brands</h2>

            <form action="{{ route('admin.vehicles.attribute-setup.storeBrand') }}" method="POST" class="mb-6">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Toyota" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                        <input type="text" name="slug" value="{{ old('slug') }}" required placeholder="e.g. toyota" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                        @error('slug') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Order</label>
                        <input type="number" name="order" value="{{ old('order', 0) }}" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                        @error('order') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Logo URL <span class="text-gray-400">(optional)</span></label>
                        <input type="text" name="logo" value="{{ old('logo') }}" placeholder="https://..." class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                        @error('logo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description <span class="text-gray-400">(optional)</span></label>
                        <textarea name="description" rows="2" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">{{ old('description') }}</textarea>
                        @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <button type="submit" class="mt-4 bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700">Add Brand</button>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($brands as $brand)
                        <tr>
                            <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $brand->name }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500 font-mono">{{ $brand->slug }}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">{{ $brand->order }}</td>
                            <td class="px-4 py-2">
                                <span class="px-2 py-1 rounded-full text-xs {{ $brand->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $brand->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-3 text-center text-sm text-gray-500">No brands yet</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Attributes</h2>

            <form action="{{ route('admin.vehicles.attribute-setup.storeAttribute') }}" method="POST" class="mb-6">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                        <select name="type" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                            @foreach(['color' => 'Color', 'seating_capacity' => 'Seating Capacity', 'fuel_type' => 'Fuel Type', 'transmission' => 'Transmission', 'feature' => 'Feature'] as $value => $label)
                            <option value="{{ $value }}" {{ old('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Order</label>
                        <input type="number" name="order" value="{{ old('order', 0) }}" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Air Conditioning" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Value</label>
                        <input type="text" name="value" value="{{ old('value') }}" required placeholder="e.g. yes" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                        @error('value') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <button type="submit" class="mt-4 bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700">Add Attribute</button>
            </form>

            @forelse($attributes as $type => $attrs)
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">{{ ucwords(str_replace('_', ' ', $type)) }}</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($attrs as $attr)
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs {{ $attr->is_active ? 'bg-gray-100 text-gray-800' : 'bg-red-50 text-red-700 line-through' }}">
                        {{ $attr->name }}: {{ $attr->value }}
                        <span class="text-gray-400">({{ $attr->order }})</span>
                    </span>
                    @endforeach
                </div>
            </div>
            @empty
            <p class="text-sm text-gray-500 text-center py-3">No attributes yet</p>
            @endforelse
        </div>
    </div>
</div>
@endsection