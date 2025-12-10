<?php

namespace App\Http\Controllers\Api;

use App\Models\Location;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\LocationResource;
use App\Http\Requests\StoreLocationRequest;
use MatanYadaev\EloquentSpatial\Objects\Point;

class LocationController extends Controller
{
    public function index()
    {
        $locations = Location::with('category')->latest()->get();
        return LocationResource::collection($locations);
    }

    public function store(StoreLocationRequest $request)
    {
        $validated = $request->validated();

        $point = new Point($validated['latitude'], $validated['longitude']);

        $location = Location::create([
            'user_id' => $request->user()->id,
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'address' => $validated['address'],
            'description' => $validated['description'],
            'coordinates' => $point,
        ]);

        return new LocationResource($location);
    }

    public function show($id)
    {
        $location = Location::with(['category', 'registrar'])->findOrFail($id);
        return new LocationResource($location);
    }

    public function destroy(Request $request, $id)
    {
        $location = Location::findOrFail($id);

        if ($request->user()->id !== $location->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $location->delete();
        return response()->json(['message' => 'Tempat berhasil dihapus']);
    }
}
