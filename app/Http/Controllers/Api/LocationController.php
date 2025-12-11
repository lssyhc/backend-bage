<?php

namespace App\Http\Controllers\Api;

use App\Models\Location;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\LocationResource;
use App\Http\Requests\StoreLocationRequest;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
            $userPoint = new Point(
                (float) $request->latitude,
                (float) $request->longitude,
                4326
            );

            $query->orderByDistance('coordinates', $userPoint, 'asc');
        } else {
            $query->orderBy('name', 'asc');
        }

        return LocationResource::collection($query->paginate(10));
    }

    public function store(StoreLocationRequest $request)
    {
        try {
            $validated = $request->validated();

            $point = new Point($validated['latitude'], $validated['longitude'], 4326);

            $location = Location::create([
                'user_id' => $request->user()->id,
                'category_id' => $validated['category_id'],
                'name' => $validated['name'],
                'address' => $validated['address'],
                'description' => $validated['description'],
                'coordinates' => $point,
            ]);

            return new LocationResource($location->load('category', 'registrar'));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menambahkan lokasi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $location = Location::with(['category', 'registrar'])->findOrFail($id);
            return new LocationResource($location);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Lokasi tidak ditemukan'], 404);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $location = Location::findOrFail($id);

            if ($request->user()->id !== $location->user_id) {
                return response()->json([
                    'message' => 'Akses Ditolak. Anda bukan pemilik lokasi ini.'
                ], 403);
            }

            $location->delete();
            return response()->json(['message' => 'Tempat berhasil dihapus']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Lokasi tidak ditemukan'], 404);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'message' => 'Lokasi tidak dapat dihapus karena memiliki postingan terkait.'
            ], 409);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan server'], 500);
        }
    }
}
