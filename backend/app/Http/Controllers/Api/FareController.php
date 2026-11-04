<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fare;
use Illuminate\Http\Request;

class FareController extends Controller
{
    public function index()
    {
        $fares = Fare::with('route')->get();

        return response()->json($fares);
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

        $fare = Fare::create($validated);

        return response()->json($fare->load('route'), 201);
    }

    public function show(string $id)
    {
        $fare = Fare::with('route')->findOrFail($id);

        return response()->json($fare);
    }

    public function update(Request $request, string $id)
    {
        $fare = Fare::findOrFail($id);
        $validated = $request->validate([
            'route_id' => 'sometimes|required|exists:routes,id',
            'amount' => 'sometimes|required|numeric|min:0',
            'fare_type' => 'sometimes|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'effective_date' => 'nullable|date',
        ]);

        $fare->update($validated);

        return response()->json($fare->load('route'));
    }

    public function destroy(string $id)
    {
        $fare = Fare::findOrFail($id);
        $fare->delete();

        return response()->json(null, 204);
    }

    public function getByRoute(string $routeId)
    {
        $fares = Fare::where('route_id', $routeId)
            ->where('is_active', true)
            ->with('route')
            ->get();

        return response()->json($fares);
    }
}
