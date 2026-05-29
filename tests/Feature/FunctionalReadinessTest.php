<?php

use App\Models\Category;
use App\Models\Location;
use App\Models\Notification;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    foreach ([
        'notifications',
        'follows',
        'likes',
        'comments',
        'posts',
        'locations',
        'categories',
        'users',
    ] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('username')->unique();
        $table->string('email')->unique();
        $table->string('password')->nullable();
        $table->text('bio')->nullable();
        $table->string('profile_picture')->nullable();
        $table->timestamps();
    });

    Schema::create('categories', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('icon')->nullable();
        $table->timestamps();
    });

    Schema::create('locations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('category_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('name');
        $table->string('address')->nullable();
        $table->string('description')->nullable();
        $table->text('coordinates')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('posts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('location_id')->constrained()->cascadeOnDelete();
        $table->text('content')->nullable();
        $table->unsignedTinyInteger('rating')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('comments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('post_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->text('content');
        $table->timestamps();
    });

    Schema::create('likes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->unsignedBigInteger('likeable_id');
        $table->string('likeable_type');
        $table->timestamps();
        $table->unique(['user_id', 'likeable_id', 'likeable_type']);
    });

    Schema::create('follows', function (Blueprint $table) {
        $table->id();
        $table->foreignId('follower_id')->constrained('users')->cascadeOnDelete();
        $table->foreignId('following_id')->constrained('users')->cascadeOnDelete();
        $table->timestamp('created_at')->nullable();
        $table->unique(['follower_id', 'following_id']);
    });

    Schema::create('notifications', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('type');
        $table->json('data');
        $table->timestamp('read_at')->nullable();
        $table->timestamps();
    });
});

function functionalUser(string $username): User
{
    return User::create([
        'name' => ucfirst($username),
        'username' => $username,
        'email' => "{$username}@example.com",
        'password' => 'secret',
    ]);
}

function functionalCategory(): Category
{
    return Category::create([
        'name' => 'Kafe',
        'icon' => 'coffee',
    ]);
}

function functionalLocation(User $owner): Location
{
    return Location::create([
        'user_id' => $owner->id,
        'category_id' => functionalCategory()->id,
        'name' => 'Tempat Uji',
        'address' => 'Jakarta',
        'description' => 'Tempat untuk uji',
    ]);
}

function functionalPost(User $author): Post
{
    return Post::create([
        'user_id' => $author->id,
        'location_id' => functionalLocation($author)->id,
        'content' => 'Unggahan uji',
        'rating' => 4,
    ]);
}

test('empty comment content returns validation error instead of server error', function () {
    $author = functionalUser('author');
    $commenter = functionalUser('commenter');
    $post = functionalPost($author);

    Sanctum::actingAs($commenter);

    $this->postJson("/api/posts/{$post->id}/comments", [
        'content' => '',
    ])->assertStatus(422);
});

test('comment creation and deletion update comment notification side effects', function () {
    $author = functionalUser('author');
    $commenter = functionalUser('commenter');
    $post = functionalPost($author);

    Sanctum::actingAs($commenter);

    $commentId = $this->postJson("/api/posts/{$post->id}/comments", [
        'content' => 'Komentar uji',
    ])
        ->assertCreated()
        ->json('data.id');

    expect(Notification::where('user_id', $author->id)->where('type', 'comment')->count())->toBe(1);

    $this->deleteJson("/api/comments/{$commentId}")
        ->assertOk();

    expect(Notification::where('user_id', $author->id)->where('type', 'comment')->count())->toBe(0);
});

test('like toggle returns authoritative like counts', function () {
    $author = functionalUser('author');
    $liker = functionalUser('liker');
    $post = functionalPost($author);

    Sanctum::actingAs($liker);

    $this->postJson("/api/posts/{$post->id}/like")
        ->assertOk()
        ->assertJsonPath('data.liked', true)
        ->assertJsonPath('data.total_likes', 1);

    $this->postJson("/api/posts/{$post->id}/like")
        ->assertOk()
        ->assertJsonPath('data.liked', false)
        ->assertJsonPath('data.total_likes', 0);
});

test('follow toggle returns authoritative follower counts and notification follow state', function () {
    $target = functionalUser('target');
    $follower = functionalUser('follower');

    Sanctum::actingAs($follower);

    $this->postJson("/api/users/{$target->id}/follow")
        ->assertOk()
        ->assertJsonPath('data.is_following', true)
        ->assertJsonPath('data.total_followers', 1);

    $target->followings()->attach($follower->id);

    Sanctum::actingAs($target);

    $this->getJson('/api/notifications')
        ->assertOk()
        ->assertJsonPath('data.0.data.is_followed', true);

    Sanctum::actingAs($follower);

    $this->postJson("/api/users/{$target->id}/follow")
        ->assertOk()
        ->assertJsonPath('data.is_following', false)
        ->assertJsonPath('data.total_followers', 0);
});

test('location with posts cannot be deleted', function () {
    $owner = functionalUser('owner');
    $location = functionalLocation($owner);

    Post::create([
        'user_id' => $owner->id,
        'location_id' => $location->id,
        'content' => 'Ulasan tempat',
    ]);

    Sanctum::actingAs($owner);

    $this->deleteJson("/api/locations/{$location->id}")
        ->assertStatus(409);

    expect($location->fresh())->not->toBeNull();
});
