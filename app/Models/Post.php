<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'location_id',
        'content',
        'rating',
        'media_type',
        'media_url'
    ];

    protected static function boot()
    {
        parent::boot();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function media()
    {
        return $this->hasMany(PostMedia::class);
    }


    public function isLikedBy(User $user)
    {
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    public function scopeWithScoring(Builder $query, float $gravity = 1.8)
    {
        $likesCountQuery = '(
            SELECT COUNT(*)
            FROM likes
            WHERE likes.likeable_id = posts.id
            AND likes.likeable_type = ?
        )';

        $commentsCountQuery = '(
            SELECT COUNT(*)
            FROM comments
            WHERE comments.post_id = posts.id
        )';

        $ageInHours = "EXTRACT(EPOCH FROM (NOW() - created_at)) / 3600";
        $pointsCalculation = "({$likesCountQuery} + ({$commentsCountQuery} * 2))";

        return $query->withCount(['likes', 'comments'])
            ->selectRaw("posts.*")
            ->selectRaw(
                "(($pointsCalculation + 1) / POWER(($ageInHours + 2), ?)) as trending_score",
                [self::class, $gravity]
            )
            ->orderByDesc('trending_score');
    }
}
