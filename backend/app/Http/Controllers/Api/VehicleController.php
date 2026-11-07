<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Support\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'driver') {
            $vehicles = Vehicle::where('driver_id', $user->id)->get();
        } else {
            $vehicles = Vehicle::with('driver')->where('is_active', true)->get();
        }

        return response()->json($vehicles);
    }

    public function store(Request $request)
    {
        if (! $request->user()->isDriver()) {
            return $this->forbidden();
        }

        $validator = Validator::make($request->all(), [
            'make' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'required|integer|min:2000|max:'.date('Y'),
            'license_plate' => 'required|string|unique:vehicles',
            'color' => 'required|string|max:50',
            'capacity' => 'required|integer|min:1|max:20',
            'vehicle_type' => 'required|in:sedan,suv,minivan,bus',
            'vehicle_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
            'registration_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
            'roadworthy_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vehicle = Vehicle::create([
            'driver_id' => $request->user()->id,
            'make' => $request->make,
            'model' => $request->model,
            'year' => $request->year,
            'license_plate' => $request->license_plate,
            'color' => $request->color,
            'capacity' => $request->capacity,
            'vehicle_type' => $request->vehicle_type,
            'vehicle_image' => Image::store($request->file('vehicle_image'), 'vehicles'),
            'registration_image' => Image::store($request->file('registration_image'), 'vehicles'),
            'roadworthy_image' => Image::store($request->file('roadworthy_image'), 'vehicles'),
            'is_active' => false,
            'request_status' => 'pending',
        ]);

        return response()->json($vehicle->load('driver'), 201);
    }

    public function show(Request $request, Vehicle $vehicle)
    {
        $user = $request->user();

        if (! $user->isAdmin() && $vehicle->driver_id !== $user->id && ! $vehicle->is_active) {
            return $this->forbidden();
        }

        $vehicle->load('driver');

        return response()->json($vehicle);
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $user = $request->user();

        if (! $user->isAdmin() && $vehicle->driver_id !== $user->id) {
            return $this->forbidden();
        }

        $validator = Validator::make($request->all(), [
            'make' => 'sometimes|required|string|max:255',
            'model' => 'sometimes|required|string|max:255',
            'year' => 'sometimes|required|integer|min:2000|max:'.date('Y'),
            'license_plate' => 'sometimes|required|string|unique:vehicles,license_plate,'.$vehicle->id,
            'color' => 'sometimes|required|string|max:50',
            'capacity' => 'sometimes|required|integer|min:1|max:20',
            'vehicle_type' => 'sometimes|required|in:sedan,suv,minivan,bus',
            'is_active' => 'sometimes|boolean',
            'vehicle_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'registration_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'roadworthy_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only([
            'make', 'model', 'year', 'license_plate', 'color',
            'capacity', 'vehicle_type',
        ]);

        foreach (['vehicle_image', 'registration_image', 'roadworthy_image'] as $field) {
            if ($request->hasFile($field)) {
                Image::delete($vehicle->{$field});
                $data[$field] = Image::store($request->file($field), 'vehicles');
            }
        }

        if ($user->isAdmin()) {
            $data['is_active'] = $request->boolean('is_active', $vehicle->is_active);
        } elseif ($vehicle->request_status !== 'pending') {
            $data = array_merge($data, [
                'request_status' => 'pending',
                'rejection_reason' => null,
                'request_approved_at' => null,
                'request_approved_by' => null,
                'is_active' => false,
            ]);
        }

        $vehicle->update($data);

        return response()->json($vehicle->load('driver'));
    }

    public function destroy(Request $request, Vehicle $vehicle)
    {
        $user = $request->user();

        if (! $user->isAdmin() && $vehicle->driver_id !== $user->id) {
            return $this->forbidden();
        }

        Image::delete($vehicle->vehicle_image);
        Image::delete($vehicle->registration_image);
        Image::delete($vehicle->roadworthy_image);
        $vehicle->delete();

        return response()->json(null, 204);
    }

    public function uploadPhoto(Request $request, Vehicle $vehicle)
    {
        $user = $request->user();

        if (! $user->isAdmin() && $vehicle->driver_id !== $user->id) {
            return $this->forbidden();
        }

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        Image::delete($vehicle->vehicle_image);
        $vehicle->update([
            'vehicle_image' => Image::store($request->file('photo'), 'vehicles'),
        ]);

        return response()->json([
            'vehicle_image_url' => $vehicle->vehicle_image_url,
        ]);
    }

    public function setAvailability(Request $request, Vehicle $vehicle)
    {
        $user = $request->user();

        if (! $user->isAdmin() && $vehicle->driver_id !== $user->id) {
            return $this->forbidden();
        }

        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (! $user->isAdmin() && $request->boolean('is_active') && $vehicle->request_status !== 'approved') {
            return response()->json([
                'message' => 'Your vehicle must be approved before you can go online.',
            ], 422);
        }

        $vehicle->update(['is_active' => $request->is_active]);

        return response()->json($vehicle);
    }

    private function forbidden()
    {
        return response()->json(['message' => 'Forbidden.'], 403);
    }
}
