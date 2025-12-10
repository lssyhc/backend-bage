<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Location extends Model
{
    use HasFactory, SoftDeletes, HasSpatial;

    protected $fillable = [
        'category_id',
        'user_id',
        'name',
        'address',
        'description',
        'coordinates',
    ];

    protected $casts = [
        'coordinates' => Point::class,
    ];


    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function registrar()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
