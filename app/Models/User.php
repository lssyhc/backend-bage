<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasSpatial;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'bio',
        'profile_picture',
        'last_location',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_location' => Point::class,
    ];

    public function locations()
    {
        return $this->hasMany(Location::class);
    }
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class)->latest();
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')
            ->withPivot('created_at');
    }

    public function followings()
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')
            ->withPivot('created_at');
    }
}
