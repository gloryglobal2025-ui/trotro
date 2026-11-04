<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAttribute;
use App\Models\VehicleBrand;
use App\Support\Image;
use Illuminate\Http\Request;

class VehicleManagementController extends Controller
{
    // Vehicle List
    public function index()
    {
        $vehicles = Vehicle::with(['driver', 'brand'])->latest()->paginate(20);

        return view('admin.vehicles.index', compact('vehicles'));
    }

    // Add New Vehicle
    public function create()
    {
        $drivers = User::where('role', 'driver')->get();
        $brands = VehicleBrand::active()->ordered()->get();
        $attributes = VehicleAttribute::active()->ordered()->get()->groupBy('type');

        return view('admin.vehicles.create', compact('drivers', 'brands', 'attributes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'driver_id' => 'required|exists:users,id',
            'brand_id' => 'nullable|exists:vehicle_brands,id',
            'make' => 'required|string',
            'model' => 'required|string',
            'year' => 'required|integer',
            'license_plate' => 'required|string|unique:vehicles,license_plate',
            'color' => 'required|string',
            'capacity' => 'required|integer',
            'vehicle_type' => 'required|in:sedan,suv,minivan,bus',
            'vehicle_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'attributes' => 'array',
            'attributes.*' => 'exists:vehicle_attributes,id',
        ]);

        $image = $request->file('vehicle_image');
        $validated['vehicle_image'] = $image ? Image::store($image, 'vehicles') : null;

        $vehicle = Vehicle::create($validated);

        if (isset($validated['attributes'])) {
            $vehicle->attributes()->attach($validated['attributes']);
        }

        return redirect()
            ->route('admin.vehicles.index')
            ->with('success', 'Vehicle created successfully.');
    }

    public function edit(Vehicle $vehicle)
    {
        $drivers = User::where('role', 'driver')->get();
        $brands = VehicleBrand::active()->ordered()->get();
        $attributes = VehicleAttribute::active()->ordered()->get()->groupBy('type');

        return view('admin.vehicles.edit', compact('vehicle', 'drivers', 'brands', 'attributes'));
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate([
            'driver_id' => 'required|exists:users,id',
            'brand_id' => 'nullable|exists:vehicle_brands,id',
            'make' => 'required|string',
            'model' => 'required|string',
            'year' => 'required|integer',
            'license_plate' => 'required|string|unique:vehicles,license_plate,'.$vehicle->id,
            'color' => 'required|string',
            'capacity' => 'required|integer',
            'vehicle_type' => 'required|in:sedan,suv,minivan,bus',
            'vehicle_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'attributes' => 'array',
            'attributes.*' => 'exists:vehicle_attributes,id',
        ]);

        $image = $request->file('vehicle_image');
        unset($validated['vehicle_image']);

        if ($image) {
            Image::delete($vehicle->vehicle_image);
            $validated['vehicle_image'] = Image::store($image, 'vehicles');
        }

        $vehicle->update($validated);

        if (isset($validated['attributes'])) {
            $vehicle->attributes()->sync($validated['attributes']);
        }

        return redirect()
            ->route('admin.vehicles.index')
            ->with('success', 'Vehicle updated successfully.');
    }

    public function destroy(Vehicle $vehicle)
    {
        Image::delete($vehicle->vehicle_image);
        $vehicle->delete();

        return redirect()
            ->route('admin.vehicles.index')
            ->with('success', 'Vehicle deleted successfully.');
    }

    // Vehicle Attribute Setup
    public function attributeSetup()
    {
        $brands = VehicleBrand::ordered()->get();
        $attributes = VehicleAttribute::ordered()->get()->groupBy('type');

        return view('admin.vehicles.attribute-setup', compact('brands', 'attributes'));
    }

    public function storeBrand(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'slug' => 'required|string|unique:vehicle_brands,slug',
            'logo' => 'nullable|string',
            'description' => 'nullable|string',
            'order' => 'required|integer',
        ]);

        VehicleBrand::create($validated);

        return redirect()
            ->route('admin.vehicles.attribute-setup')
            ->with('success', 'Brand created successfully.');
    }

    public function storeAttribute(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:color,seating_capacity,fuel_type,transmission,feature',
            'name' => 'required|string',
            'value' => 'required|string',
            'order' => 'required|integer',
        ]);

        VehicleAttribute::create($validated);

        return redirect()
            ->route('admin.vehicles.attribute-setup')
            ->with('success', 'Attribute created successfully.');
    }

    // New Vehicle Request List
    public function newRequests()
    {
        $vehicles = Vehicle::pending()->with(['driver', 'brand'])->latest()->paginate(20);

        return view('admin.vehicles.new-requests', compact('vehicles'));
    }

    public function approveRequest(Vehicle $vehicle)
    {
        $vehicle->update([
            'request_status' => 'approved',
            'request_approved_at' => now(),
            'request_approved_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.vehicles.new-requests')
            ->with('success', 'Vehicle request approved successfully.');
    }

    public function rejectRequest(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate(['rejection_reason' => 'nullable|string']);

        $vehicle->update([
            'request_status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
            'request_approved_at' => now(),
            'request_approved_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.vehicles.new-requests')
            ->with('success', 'Vehicle request rejected successfully.');
    }

    // Update Vehicle Request List
    public function updateRequests()
    {
        $vehicles = Vehicle::whereIn('request_status', ['pending', 'rejected'])
            ->with(['driver', 'brand'])
            ->latest()
            ->paginate(20);

        return view('admin.vehicles.update-requests', compact('vehicles'));
    }
}
