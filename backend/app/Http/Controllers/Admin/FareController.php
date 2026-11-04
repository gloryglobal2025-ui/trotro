<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fare;
use App\Models\Route;
use Illuminate\Http\Request;

class FareController extends Controller
{
    public function index()
    {
        $fares = Fare::with('route')->latest()->paginate(10);

        return view('admin.fares.index', compact('fares'));
    }

    public function create()
    {
        $routes = Route::with(['origin', 'destination'])->get();

        return view('admin.fares.create', compact('routes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'route_id' => 'required|exists:routes,id',
            'amount' => 'required|numeric|min:0',
            'fare_type' => 'sometimes|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'effective_date' => 'nullable|date',
        ]);

        $validated['is_active'] = $request->has('is_active');

        Fare::create($validated);

        return redirect()->route('admin.fares.index')
            ->with('success', 'Fare created successfully.');
    }

    public function show(Fare $fare)
    {
        $fare->load('route');

        return view('admin.fares.show', compact('fare'));
    }

    public function edit(Fare $fare)
    {
        $routes = Route::with(['origin', 'destination'])->get();

        return view('admin.fares.edit', compact('fare', 'routes'));
    }

    public function update(Request $request, Fare $fare)
    {
        $validated = $request->validate([
            'route_id' => 'sometimes|required|exists:routes,id',
            'amount' => 'sometimes|required|numeric|min:0',
            'fare_type' => 'sometimes|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'effective_date' => 'nullable|date',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $fare->update($validated);

        return redirect()->route('admin.fares.index')
            ->with('success', 'Fare updated successfully.');
    }

    public function destroy(Fare $fare)
    {
        $fare->delete();

        return redirect()->route('admin.fares.index')
            ->with('success', 'Fare deleted successfully.');
    }
}
