<?php

namespace App\Http\Controllers\Api;

use App\Models\Location;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\LocationResource;
use App\Http\Resources\PostResource;
use App\Http\Requests\StoreLocationRequest;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class LocationController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
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

            return $this->successResponse(
                LocationResource::collection($query->paginate(10)),
                'Data tempat berhasil dimuat.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memuat daftar tempat.', 500, $e);
        }
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
                'description' => $validated['description'] ?? '',
                'coordinates' => $point,
            ]);

            return $this->successResponse(
                new LocationResource($location->load('category', 'registrar')),
                'Tempat baru berhasil ditambahkan.',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menyimpan tempat baru. Mohon periksa koneksi Anda.', 500, $e);
        }
    }

    public function show($id)
    {
        try {
            $location = Location::with(['category', 'registrar'])->findOrFail($id);
            return $this->successResponse(new LocationResource($location), 'Detail tempat ditemukan.');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Tempat yang Anda cari tidak ditemukan.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memuat detail tempat.', 500, $e);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $location = Location::findOrFail($id);

            if ($request->user()->id !== $location->user_id) {
                return $this->errorResponse('Anda hanya dapat menghapus tempat yang Anda buat sendiri.', 403);
            }

            if ($location->posts()->exists()) {
                return $this->errorResponse(
                    'Tempat ini tidak dapat dihapus karena sudah memiliki ulasan/unggahan dari pengguna lain.',
                    409
                );
            }

            $location->delete();
            return $this->successResponse(null, 'Tempat berhasil dihapus.');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Tempat tidak ditemukan.', 404);
        } catch (\Illuminate\Database\QueryException $e) {
            return $this->errorResponse(
                'Tempat ini tidak dapat dihapus karena sudah memiliki ulasan/unggahan dari pengguna lain.',
                409,
                $e
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Terjadi kesalahan saat menghapus tempat.', 500, $e);
        }
    }

    public function posts(Request $request, $id)
    {
        try {
            $location = Location::findOrFail($id);

            $posts = \App\Models\Post::where('location_id', $id)
                ->with(['user', 'location', 'likes', 'comments.user'])
                ->latest()
                ->paginate(10);

            return $this->successResponse(
                PostResource::collection($posts),
                'Unggahan di tempat berhasil dimuat.'
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Tempat tidak ditemukan.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memuat unggahan.', 500, $e);
        }
    }
}
