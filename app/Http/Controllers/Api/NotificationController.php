<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        $notifications->getCollection()->transform(function ($notification) use ($request) {
            if ($notification->type !== 'follow') {
                return $notification;
            }

            $data = $notification->data;
            $followerId = $data['follower_id'] ?? null;

            if ($followerId) {
                $data['is_followed'] = $request->user()
                    ->followings()
                    ->where('following_id', $followerId)
                    ->exists();

                $notification->data = $data;
            }

            return $notification;
        });

        $request->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json($notifications);
    }
}
