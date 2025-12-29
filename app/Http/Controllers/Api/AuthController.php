<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request)
    {
        try {
            $validated = $request->validated();

            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $token = $user->createToken('auth_token', ['*'], now()->addMinutes(config('sanctum.expiration')))->plainTextToken;

            $cookie = cookie('token', $token, 60 * 24 * 365, null, null, false, true);

            return $this->successResponse([
                'user' => $user,
            ], 'Selamat datang! Akun Anda berhasil dibuat.', 201)->withCookie($cookie);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mendaftarkan akun. Silakan coba sesaat lagi.', 500, $e);
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $credentials = $request->validated();
            $field = filter_var($credentials['credential'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

            $loginData = [
                $field => $credentials['credential'],
                'password' => $credentials['password']
            ];

            if (!Auth::attempt($loginData)) {
                return $this->errorResponse('Kombinasi email/username dan password tidak ditemukan.', 401);
            }

            $user = User::where($field, $credentials['credential'])->firstOrFail();
            $token = $user->createToken('auth_token', ['*'], now()->addMinutes(config('sanctum.expiration')))->plainTextToken;

            $cookie = cookie('token', $token, 60 * 24 * 365, null, null, false, true);

            return $this->successResponse([
                'user' => $user,
            ], 'Login berhasil. Selamat datang kembali!')->withCookie($cookie);
        } catch (\Exception $e) {
            return $this->errorResponse('Terjadi kendala saat login.', 500, $e);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            $cookie = cookie()->forget('token');
            return $this->successResponse(null, 'Anda berhasil keluar (logout).')->withCookie($cookie);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memproses logout.', 500, $e);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $user = $request->user();
            $user->tokens()->delete(); // Revoke all tokens
            $user->delete(); // Soft delete or force delete based on model configuration

            $cookie = cookie()->forget('token');

            return $this->successResponse(null, 'Akun berhasil dihapus.')->withCookie($cookie);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus akun.', 500, $e);
        }
    }
}
