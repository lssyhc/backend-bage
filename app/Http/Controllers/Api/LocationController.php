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
    public function index(Request $request)
    {
        $query = Location::with('category');

        if ($request->filled('search')) {
            $query->where('name', 'ilike', '%' . $request->search . '%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled(['latitude', 'longitude'])) {
            $lat = (float) $request->latitude;
            $lng = (float) $request->longitude;
            $radius = (int) $request->input('radius', 5000);
            $userPoint = new Point($lat, $lng, 4326);

            $query->whereDistance('coordinates', $userPoint, '<', $radius);
            $query->orderByDistance('coordinates', $userPoint);
        } else {
            $query->latest();
        }

        return LocationResource::collection($query->get());
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
