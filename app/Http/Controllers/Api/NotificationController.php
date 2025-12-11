<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        $request->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);
        return response()->json($notifications);
    }
}
