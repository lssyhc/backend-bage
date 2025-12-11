<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Notification;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class FollowController extends Controller
{
    use ApiResponse;

    public function toggle(Request $request, $userId)
    {
        try {
            $targetUser = User::findOrFail($userId);
            $currentUser = $request->user();

            if ($targetUser->id === $currentUser->id) {
                return $this->errorResponse('Anda tidak dapat mengikuti diri sendiri.', 400);
            }

            $isFollowing = $currentUser->followings()->where('following_id', $targetUser->id)->exists();

            if ($isFollowing) {
                $currentUser->followings()->detach($targetUser->id);
                return $this->successResponse(['status' => 'unfollowed'], 'Berhenti mengikuti.');
            } else {
                $currentUser->followings()->attach($targetUser->id);

                Notification::create([
                    'user_id' => $targetUser->id,
                    'type' => 'follow',
                    'data' => [
                        'follower_id' => $currentUser->id,
                        'follower_username' => $currentUser->username,
                        'follower_avatar' => $currentUser->profile_picture,
                        'message' => "{$currentUser->username} mulai mengikuti Anda."
                    ]
                ]);

                return $this->successResponse(['status' => 'followed'], 'Berhasil mengikuti.');
            }
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Pengguna tidak ditemukan.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memproses permintaan follow.', 500, $e);
        }
    }
}
