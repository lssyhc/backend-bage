<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\UpdateProfileRequest;

class ProfileController extends Controller
{
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

            return response()->json([
                'message' => 'Profil berhasil diperbarui',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'bio' => $user->bio,
                    'email' => $user->email,
                    'profile_picture_url' => $user->profile_picture ? url(Storage::url($user->profile_picture)) : null,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memperbarui profil',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
