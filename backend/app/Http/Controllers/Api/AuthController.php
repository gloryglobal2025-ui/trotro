<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'required|string',
            'role' => 'required|in:passenger,driver',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role' => $request->role,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('vehicle'));
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->update([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
        ]);

        return response()->json([
            'user' => $user->fresh()->load('vehicle'),
        ]);
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $user = $request->user();

        Image::delete($user->profile_image);
        $user->update([
            'profile_image' => Image::store($request->file('photo'), 'profiles'),
        ]);

        return response()->json([
            'profile_image_url' => $user->profile_image_url,
        ]);
    }

    public function submitDriverVerification(Request $request)
    {
        $user = $request->user();

        if (! $user->isDriver()) {
            return response()->json(['message' => 'Only drivers can submit verification documents.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'license_number' => 'required|string|max:50',
            'license_expiry' => 'required|date|after:today',
            'ghana_card_number' => 'required|string|max:50',
            'license_image' => [
                $user->license_image ? 'nullable' : 'required',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:4096',
            ],
            'ghana_card_image' => [
                $user->ghana_card_image ? 'nullable' : 'required',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:4096',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = [
            'license_number' => $request->input('license_number'),
            'license_expiry' => $request->input('license_expiry'),
            'ghana_card_number' => $request->input('ghana_card_number'),
            'verification_status' => 'pending',
            'verification_notes' => null,
            'verification_submitted_at' => now(),
            'verified_at' => null,
            'verified_by' => null,
        ];

        foreach (['license_image', 'ghana_card_image'] as $field) {
            if ($request->hasFile($field)) {
                Image::delete($user->{$field});
                $data[$field] = Image::store($request->file($field), 'drivers');
            }
        }

        $user->update($data);

        return response()->json([
            'user' => $user->fresh()->load('vehicle'),
            'message' => 'Documents submitted. An administrator will review your account shortly.',
        ]);
    }
}
