<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class FollowController extends Controller
{
    public function toggle(Request $request, $userId)
    {
        $targetUser = User::findOrFail($userId);
        $currentUser = $request->user();

        if ($targetUser->id === $currentUser->id) {
            return response()->json(['message' => 'Tidak bisa follow diri sendiri'], 400);
        }

        $isFollowing = $currentUser->followings()->where('following_id', $targetUser->id)->exists();

        if ($isFollowing) {
            $currentUser->followings()->detach($targetUser->id);
            return response()->json(['message' => 'Unfollowed', 'status' => 'unfollowed']);
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

            return response()->json(['message' => 'Followed', 'status' => 'followed']);
        }
    }
}
