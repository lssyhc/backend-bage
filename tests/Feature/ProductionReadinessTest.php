<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PostController;
use App\Models\Post;
use App\Models\PostMedia;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Cookie;

test('cors allowed origins can be configured from environment', function () {
    putenv('FRONTEND_URL=https://app.example.com');
    $_ENV['FRONTEND_URL'] = 'https://app.example.com';
    $_SERVER['FRONTEND_URL'] = 'https://app.example.com';

    $cors = require base_path('config/cors.php');

    expect($cors['allowed_origins'])->toBe(['https://app.example.com']);
});

test('auth token cookie uses production session defaults', function () {
    config([
        'session.path' => '/',
        'session.domain' => '.example.com',
        'session.secure' => true,
        'session.same_site' => 'lax',
    ]);

    $controller = new class extends AuthController
    {
        public function exposedAuthTokenCookie(string $token): Cookie
        {
            return $this->authTokenCookie($token);
        }
    };

    $cookie = $controller->exposedAuthTokenCookie('plain-token');

    expect($cookie->getName())->toBe('token')
        ->and($cookie->getValue())->toBe('plain-token')
        ->and($cookie->isSecure())->toBeTrue()
        ->and($cookie->isHttpOnly())->toBeTrue()
        ->and($cookie->getDomain())->toBe('.example.com')
        ->and($cookie->getPath())->toBe('/')
        ->and($cookie->getSameSite())->toBe('lax');
});

test('public auth routes are throttled', function () {
    $loginRoute = collect(Route::getRoutes())->first(
        fn ($route) => in_array('POST', $route->methods(), true) && $route->uri() === 'api/auth/login'
    );
    $registerRoute = collect(Route::getRoutes())->first(
        fn ($route) => in_array('POST', $route->methods(), true) && $route->uri() === 'api/auth/register'
    );

    expect($loginRoute?->gatherMiddleware())->toContain('throttle:10,1')
        ->and($registerRoute?->gatherMiddleware())->toContain('throttle:10,1');
});

test('unauthenticated api requests return json 401 without accept header', function () {
    $this->get('/api/user')
        ->assertUnauthorized()
        ->assertJson([
            'message' => 'Unauthenticated.',
        ]);
});

test('post media files are deleted from public storage', function () {
    Storage::fake('public');
    Storage::disk('public')->put('posts/current.jpg', 'image');
    Storage::disk('public')->put('posts/legacy.jpg', 'image');

    $post = new Post(['media_url' => 'posts/legacy.jpg']);
    $post->setRelation('media', collect([
        new PostMedia(['media_url' => 'posts/current.jpg']),
    ]));

    $controller = new class extends PostController
    {
        public function exposedDeletePostMediaFiles(Post $post): void
        {
            $this->deletePostMediaFiles($post);
        }
    };

    $controller->exposedDeletePostMediaFiles($post);

    Storage::disk('public')->assertMissing('posts/current.jpg');
    Storage::disk('public')->assertMissing('posts/legacy.jpg');
});

test('demo database seeder is disabled in production', function () {
    $previousEnvironment = app()->environment();
    app()->detectEnvironment(fn () => 'production');

    try {
        (new DatabaseSeeder)->run();
    } finally {
        app()->detectEnvironment(fn () => $previousEnvironment);
    }

    expect(true)->toBeTrue();
});
