<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Traits\ApiResponse;
use App\Models\Notification;
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

            $result = DB::transaction(function () use ($currentUser, $targetUser) {
                $existingFollow = $currentUser->followings()
                    ->where('following_id', $targetUser->id)
                    ->exists();

                if ($existingFollow) {
                    $currentUser->followings()->detach($targetUser->id);
                    return [
                        'is_following' => false,
                        'message' => "Berhenti mengikuti {$targetUser->username}."
                    ];
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

                    return [
                        'is_following' => true,
                        'message' => "Mulai mengikuti {$targetUser->username}."
                    ];
                }
            });

            $targetUser->loadCount('followers');

            return $this->successResponse([
                'is_following' => $result['is_following'],
                'total_followers' => $targetUser->followers_count,
                'user_id' => $targetUser->id
            ], $result['message']);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Pengguna tidak ditemukan.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memproses permintaan follow.', 500, $e);
        }
    }
}
