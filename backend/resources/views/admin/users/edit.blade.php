@extends('admin.layout')

@section('title', 'Edit User')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <a href="{{ route('admin.users.index') }}" class="text-orange-600 hover:text-orange-800">← Back to Users</a>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-6">Edit User: {{ $user->name }}</h1>

    <form action="{{ route('admin.users.update', $user) }}" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-lg shadow">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Password (leave blank to keep current)</label>
                <input type="password" name="password" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                <select name="role" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <option value="passenger" {{ $user->role === 'passenger' ? 'selected' : '' }}>Passenger</option>
                    <option value="driver" {{ $user->role === 'driver' ? 'selected' : '' }}>Driver</option>
                    <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">User Level</label>
                <select name="user_level_id" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <option value="">Select Level (Optional)</option>
                    @foreach($levels as $level)
                    <option value="{{ $level->id }}" {{ $user->user_level_id === $level->id ? 'selected' : '' }}>{{ $level->name }} ({{ $level->user_type }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Profile Photo</label>
                <div class="flex items-center gap-4">
                    @if($user->profile_image_url)
                        <img src="{{ $user->profile_image_url }}" alt="{{ $user->name }}" class="h-16 w-16 rounded-full border object-cover">
                    @else
                        <span class="flex h-16 w-16 items-center justify-center rounded-full bg-orange-100 text-lg font-bold text-orange-700">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                    @endif
                    <div class="flex-1">
                        <input type="file" name="profile_image" accept="image/jpeg,image/png,image/webp" class="w-full px-3 py-2 border rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <p class="mt-1 text-xs text-gray-500">JPG, PNG or WebP up to 2 MB.</p>
                        @error('profile_image') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6">
            <button type="submit" class="bg-orange-600 text-white px-6 py-2 rounded-lg hover:bg-orange-700">
                Update User
            </button>
        </div>
    </form>
</div>
@endsection
