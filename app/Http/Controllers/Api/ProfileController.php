<?php

namespace App\Http\Controllers\Api;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\UpdateProfileRequest;

class ProfileController extends Controller
{
    use ApiResponse;

    public function update(UpdateProfileRequest $request)
    {
        try {
            $user = $request->user();
            $validated = $request->validated();

            if ($request->hasFile('profile_picture')) {
                if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
                    Storage::disk('public')->delete($user->profile_picture);
                }

                $path = $request->file('profile_picture')->store('avatars', 'public');

                if (!$path) {
                    return $this->errorResponse('Gagal mengunggah foto profil. Pastikan format file sesuai.', 422);
                }

                $user->profile_picture = $path;
            }

            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            if (isset($validated['name'])) $user->name = $validated['name'];
            if (isset($validated['username'])) $user->username = $validated['username'];
            if (isset($validated['email'])) $user->email = $validated['email'];
            if (isset($validated['bio'])) $user->bio = $validated['bio'];

            $user->save();

            $responseData = [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'bio' => $user->bio,
                'email' => $user->email,
                'profile_picture_url' => $user->profile_picture ? url(Storage::url($user->profile_picture)) : null,
            ];

            return $this->successResponse($responseData, 'Profil Anda berhasil diperbarui.');
        } catch (\Exception $e) {
            return $this->errorResponse('Maaf, kami gagal memperbarui profil Anda saat ini.', 500, $e);
        }
    }
}
