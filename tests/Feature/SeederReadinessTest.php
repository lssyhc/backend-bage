<?php

use App\Models\Category;
use App\Models\Comment;
use App\Models\Location;
use App\Models\Notification;
use App\Models\Post;
use App\Models\PostMedia;
use App\Models\User;
use Database\Seeders\DemoScenarioSeeder;
use Database\Seeders\ProductionDemoSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    seedReadinessResetSchema();
});

function seedReadinessResetSchema(): void
{
    foreach ([
        'seed_records',
        'notifications',
        'follows',
        'likes',
        'comments',
        'post_media',
        'posts',
        'locations',
        'categories',
        'personal_access_tokens',
        'users',
    ] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('username', 50)->unique();
        $table->string('email', 100)->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->string('name', 100);
        $table->string('bio', 150)->nullable();
        $table->text('profile_picture')->nullable();
        $table->rememberToken();
        $table->timestamps();
    });

    Schema::create('personal_access_tokens', function (Blueprint $table) {
        $table->id();
        $table->morphs('tokenable');
        $table->string('name');
        $table->string('token', 64)->unique();
        $table->text('abilities')->nullable();
        $table->timestamp('last_used_at')->nullable();
        $table->timestamp('expires_at')->nullable();
        $table->timestamps();
    });

    Schema::create('categories', function (Blueprint $table) {
        $table->id();
        $table->string('name', 50)->unique();
        $table->string('icon')->nullable();
        $table->timestamps();
    });

    Schema::create('locations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('category_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('name', 100);
        $table->string('address', 150);
        $table->string('description', 150);
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
        $table->string('media_type', 20)->nullable();
        $table->text('media_url')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('post_media', function (Blueprint $table) {
        $table->id();
        $table->foreignId('post_id')->constrained()->cascadeOnDelete();
        $table->string('media_type', 20);
        $table->text('media_url');
        $table->timestamps();
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

    Schema::create('seed_records', function (Blueprint $table) {
        $table->id();
        $table->string('key')->unique();
        $table->nullableMorphs('recordable');
        $table->json('meta')->nullable();
        $table->timestamps();
    });
}

function seedReadinessRunAs(string $environment, Closure $callback): mixed
{
    $previousEnvironment = app()->environment();
    app()->detectEnvironment(fn () => $environment);

    try {
        return $callback();
    } finally {
        app()->detectEnvironment(fn () => $previousEnvironment);
        putenv('PRODUCTION_DEMO_SEED_ENABLED');
        putenv('PRODUCTION_DEMO_PASSWORD');
        putenv('DEMO_SEED_PASSWORD');
    }
}

test('reference data seeder is production safe and idempotent', function () {
    seedReadinessRunAs('production', function () {
        (new ReferenceDataSeeder)->run();
        (new ReferenceDataSeeder)->run();
    });

    expect(Category::count())->toBe(6)
        ->and(Category::pluck('icon', 'name')->all())->toMatchArray([
            'Kafe' => 'coffee',
            'Restoran' => 'utensils',
            'Taman' => 'tree',
            'Museum' => 'landmark',
            'Hotel' => 'hotel',
            'Wisata Alam' => 'mountain',
        ]);
});

test('production demo seeder is disabled unless explicitly enabled', function () {
    seedReadinessRunAs('production', function () {
        putenv('PRODUCTION_DEMO_SEED_ENABLED=false');

        (new ProductionDemoSeeder)->run();
    });

    expect(User::count())->toBe(0)
        ->and(Location::count())->toBe(0)
        ->and(Post::count())->toBe(0);
});

test('production demo seeder requires a password when enabled', function () {
    seedReadinessRunAs('production', function () {
        putenv('PRODUCTION_DEMO_SEED_ENABLED=true');
        putenv('PRODUCTION_DEMO_PASSWORD');

        (new ProductionDemoSeeder)->run();
    });
})->throws(RuntimeException::class, 'PRODUCTION_DEMO_PASSWORD is required');

test('production demo seeder creates a realistic idempotent medium dataset', function () {
    Storage::fake('public');

    seedReadinessRunAs('production', function () {
        putenv('PRODUCTION_DEMO_SEED_ENABLED=true');
        putenv('PRODUCTION_DEMO_PASSWORD=Str0ng!DemoSeed123');

        (new ProductionDemoSeeder)->run();
        $firstCounts = [
            'users' => User::count(),
            'categories' => Category::count(),
            'locations' => Location::count(),
            'posts' => Post::count(),
            'media' => PostMedia::count(),
            'comments' => Comment::count(),
            'likes' => DB::table('likes')->count(),
            'follows' => DB::table('follows')->count(),
            'notifications' => Notification::count(),
        ];

        (new ProductionDemoSeeder)->run();

        expect([
            'users' => User::count(),
            'categories' => Category::count(),
            'locations' => Location::count(),
            'posts' => Post::count(),
            'media' => PostMedia::count(),
            'comments' => Comment::count(),
            'likes' => DB::table('likes')->count(),
            'follows' => DB::table('follows')->count(),
            'notifications' => Notification::count(),
        ])->toBe($firstCounts);
    });

    expect(User::pluck('username')->sort()->values()->all())->toBe([
        'alif_taman',
        'bayu_viewer',
        'dimas_kopi',
        'mediauser',
        'mira_notifikasi',
        'naya_kuliner',
        'raka_jalan',
        'sari_budaya',
        'testuser',
    ])
        ->and(Category::count())->toBe(6)
        ->and(Location::count())->toBe(24)
        ->and(Post::count())->toBe(60)
        ->and(Post::whereNull('content')->exists())->toBeTrue()
        ->and(Post::whereNull('rating')->exists())->toBeTrue()
        ->and(Post::where('rating', 5)->exists())->toBeTrue()
        ->and(PostMedia::count())->toBeGreaterThanOrEqual(60)
        ->and(Comment::count())->toBeGreaterThan(20)
        ->and(DB::table('likes')->count())->toBeGreaterThan(80)
        ->and(DB::table('follows')->count())->toBeGreaterThan(10)
        ->and(Notification::whereNull('read_at')->exists())->toBeTrue()
        ->and(Notification::whereNotNull('read_at')->exists())->toBeTrue();

    User::all()->each(function (User $user) {
        expect(Hash::check('Str0ng!DemoSeed123', $user->password))->toBeTrue();

        if ($user->profile_picture) {
            Storage::disk('public')->assertExists($user->profile_picture);
        }
    });

    PostMedia::all()->each(function (PostMedia $media) {
        expect($media->media_type)->toBe('image');
        Storage::disk('public')->assertExists($media->media_url);
    });
});

test('demo scenario seeder remains blocked in production', function () {
    seedReadinessRunAs('production', function () {
        putenv('DEMO_SEED_PASSWORD=Str0ng!DemoSeed123');

        (new DemoScenarioSeeder)->run();
    });

    expect(User::count())->toBe(0)
        ->and(Post::count())->toBe(0);
});

test('demo scenario seeder can build the same deterministic dataset outside production', function () {
    Storage::fake('public');

    seedReadinessRunAs('local', function () {
        putenv('DEMO_SEED_PASSWORD=Str0ng!DemoSeed123');

        $this->seed(DemoScenarioSeeder::class);
        $this->seed(DemoScenarioSeeder::class);
    });

    expect(User::where('username', 'testuser')->exists())->toBeTrue()
        ->and(Category::count())->toBe(6)
        ->and(Location::count())->toBe(24)
        ->and(Post::count())->toBe(60)
        ->and(PostMedia::count())->toBeGreaterThanOrEqual(60);
});
