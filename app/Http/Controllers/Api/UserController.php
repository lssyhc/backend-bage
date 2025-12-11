<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $sort = $request->query('sort', 'top');
        $query = User::where('username', 'ilike', '%' . $search . '%')
            ->orWhere('name', 'ilike', '%' . $search . '%');

        if ($sort === 'top') {
            $query->withCount('followers')
                ->orderByDesc('followers_count');
        } else {
            $query->latest();
        }

        return UserResource::collection($query->paginate(10));
    }

    public function show($username)
    {
        $user = User::where('username', $username)->firstOrFail();
        return new UserResource($user);
    }
}
