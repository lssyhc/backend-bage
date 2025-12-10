<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;

class UserController extends Controller
{
    public function show($username)
    {
        $user = User::where('username', $username)->firstOrFail();
        return new UserResource($user);
    }
}
