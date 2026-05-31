<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Location;
use App\Models\Notification;
use App\Models\Post;
use App\Models\PostMedia;
use App\Models\SeedRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use MatanYadaev\EloquentSpatial\Objects\Point;
use RuntimeException;

class ProductionDemoSeeder extends Seeder
{
    private const ASSET_ROOT = __DIR__.'/assets/demo';

    private const CATEGORY_ASSET_SLUGS = [
        'Kafe' => 'cafe',
        'Restoran' => 'restaurant',
        'Taman' => 'park',
        'Museum' => 'museum',
        'Hotel' => 'hotel',
        'Wisata Alam' => 'nature',
    ];

    private const USERS = [
        ['username' => 'testuser', 'name' => 'Test User', 'bio' => 'Akun demo utama untuk mencoba Bage.'],
        ['username' => 'raka_jalan', 'name' => 'Raka Jalan', 'bio' => 'Mencatat tempat menarik dari perjalanan harian di Jakarta.'],
        ['username' => 'naya_kuliner', 'name' => 'Naya Kuliner', 'bio' => 'Berburu rasa, kafe, dan restoran kecil yang nyaman.'],
        ['username' => 'dimas_kopi', 'name' => 'Dimas Kopi', 'bio' => 'Pencari sudut kafe tenang untuk kerja dan ngobrol.'],
        ['username' => 'sari_budaya', 'name' => 'Sari Budaya', 'bio' => 'Suka museum, galeri, dan cerita kota.'],
        ['username' => 'alif_taman', 'name' => 'Alif Taman', 'bio' => 'Mencari ruang hijau dan tempat jalan sore.'],
        ['username' => 'mira_notifikasi', 'name' => 'Mira Notifikasi', 'bio' => 'Akun demo untuk melihat aktivitas sosial.'],
        ['username' => 'bayu_viewer', 'name' => 'Bayu Viewer', 'bio' => 'Mengikuti banyak akun untuk melihat feed following.'],
        ['username' => 'mediauser', 'name' => 'Media User', 'bio' => null],
    ];

    private const LOCATIONS = [
        ['name' => 'Kopi Tepi Senayan', 'category' => 'Kafe', 'owner' => 'dimas_kopi', 'address' => 'Jl. Asia Afrika No. 11, Jakarta', 'description' => 'Kafe tenang dekat area Senayan dengan meja kerja yang lega.', 'lat' => -6.2255, 'lng' => 106.8005],
        ['name' => 'Ruang Seduh Melawai', 'category' => 'Kafe', 'owner' => 'dimas_kopi', 'address' => 'Jl. Melawai Raya No. 22, Jakarta', 'description' => 'Tempat ngopi kecil dengan manual brew dan playlist santai.', 'lat' => -6.2444, 'lng' => 106.7996],
        ['name' => 'Kedai Pagi Cikini', 'category' => 'Kafe', 'owner' => 'testuser', 'address' => 'Jl. Cikini Raya No. 15, Jakarta', 'description' => 'Kedai pagi dengan roti bakar dan kopi susu yang konsisten.', 'lat' => -6.1909, 'lng' => 106.8386],
        ['name' => 'Nasi Hangat Tebet', 'category' => 'Restoran', 'owner' => 'naya_kuliner', 'address' => 'Jl. Tebet Timur Dalam No. 8, Jakarta', 'description' => 'Restoran rumahan dengan menu nasi hangat dan lauk harian.', 'lat' => -6.2328, 'lng' => 106.8562],
        ['name' => 'Soto Kota Menteng', 'category' => 'Restoran', 'owner' => 'naya_kuliner', 'address' => 'Jl. HOS Cokroaminoto No. 41, Jakarta', 'description' => 'Soto bening dengan kuah ringan dan tempat yang rapi.', 'lat' => -6.1961, 'lng' => 106.8272],
        ['name' => 'Dapur Sore Kemang', 'category' => 'Restoran', 'owner' => 'raka_jalan', 'address' => 'Jl. Kemang Raya No. 29, Jakarta', 'description' => 'Tempat makan sore dengan area luar dan pilihan menu keluarga.', 'lat' => -6.2608, 'lng' => 106.8163],
        ['name' => 'Taman Literasi Blok M', 'category' => 'Taman', 'owner' => 'alif_taman', 'address' => 'Jl. Sisingamangaraja, Jakarta', 'description' => 'Ruang publik dekat transit dengan area duduk dan pepohonan.', 'lat' => -6.2440, 'lng' => 106.8003],
        ['name' => 'Taman Suropati', 'category' => 'Taman', 'owner' => 'alif_taman', 'address' => 'Jl. Taman Suropati, Jakarta', 'description' => 'Taman klasik untuk jalan sore dan melihat komunitas musik.', 'lat' => -6.1994, 'lng' => 106.8326],
        ['name' => 'Hutan Kota GBK', 'category' => 'Taman', 'owner' => 'bayu_viewer', 'address' => 'Kompleks Gelora Bung Karno, Jakarta', 'description' => 'Area hijau luas untuk piknik singkat di tengah kota.', 'lat' => -6.2186, 'lng' => 106.8037],
        ['name' => 'Museum Nasional', 'category' => 'Museum', 'owner' => 'sari_budaya', 'address' => 'Jl. Medan Merdeka Barat No. 12, Jakarta', 'description' => 'Museum sejarah dan budaya dengan koleksi arkeologi Indonesia.', 'lat' => -6.1764, 'lng' => 106.8216],
        ['name' => 'Museum Tekstil', 'category' => 'Museum', 'owner' => 'sari_budaya', 'address' => 'Jl. KS Tubun No. 2-4, Jakarta', 'description' => 'Museum kecil dengan koleksi kain dan ruang workshop batik.', 'lat' => -6.1945, 'lng' => 106.8100],
        ['name' => 'Galeri Kota Tua', 'category' => 'Museum', 'owner' => 'mira_notifikasi', 'address' => 'Jl. Pintu Besar Utara, Jakarta', 'description' => 'Galeri dekat kawasan Kota Tua untuk melihat cerita kota lama.', 'lat' => -6.1352, 'lng' => 106.8133],
        ['name' => 'Hotel Transit Sudirman', 'category' => 'Hotel', 'owner' => 'bayu_viewer', 'address' => 'Jl. Jend. Sudirman No. 52, Jakarta', 'description' => 'Hotel praktis dekat MRT dengan akses cepat ke pusat bisnis.', 'lat' => -6.2146, 'lng' => 106.8214],
        ['name' => 'Urban Stay Kuningan', 'category' => 'Hotel', 'owner' => 'testuser', 'address' => 'Jl. HR Rasuna Said No. 18, Jakarta', 'description' => 'Penginapan modern untuk perjalanan kerja singkat.', 'lat' => -6.2192, 'lng' => 106.8326],
        ['name' => 'Guest House Cikajang', 'category' => 'Hotel', 'owner' => 'raka_jalan', 'address' => 'Jl. Cikajang No. 7, Jakarta', 'description' => 'Guest house tenang dekat restoran dan area jalan kaki.', 'lat' => -6.2387, 'lng' => 106.8091],
        ['name' => 'Setu Babakan', 'category' => 'Wisata Alam', 'owner' => 'alif_taman', 'address' => 'Srengseng Sawah, Jakarta', 'description' => 'Danau dan kawasan budaya Betawi untuk jalan santai.', 'lat' => -6.3417, 'lng' => 106.8232],
        ['name' => 'Mangrove Angke', 'category' => 'Wisata Alam', 'owner' => 'mediauser', 'address' => 'Pantai Indah Kapuk, Jakarta', 'description' => 'Area mangrove dengan jalur kayu dan suasana teduh.', 'lat' => -6.1052, 'lng' => 106.7351],
        ['name' => 'Taman Waduk Pluit', 'category' => 'Wisata Alam', 'owner' => 'mediauser', 'address' => 'Jl. Pluit Timur Raya, Jakarta', 'description' => 'Ruang terbuka di tepi waduk untuk olahraga ringan.', 'lat' => -6.1206, 'lng' => 106.7907],
        ['name' => 'Kopi Lorong Kota', 'category' => 'Kafe', 'owner' => 'naya_kuliner', 'address' => 'Jl. Kali Besar Timur, Jakarta', 'description' => 'Kafe kecil di area Kota Tua dengan nuansa bangunan lama.', 'lat' => -6.1358, 'lng' => 106.8122],
        ['name' => 'Bakmi Senja Kelapa Gading', 'category' => 'Restoran', 'owner' => 'testuser', 'address' => 'Jl. Boulevard Raya No. 19, Jakarta', 'description' => 'Bakmi sederhana dengan porsi pas untuk makan malam cepat.', 'lat' => -6.1584, 'lng' => 106.9082],
        ['name' => 'Taman Langsat', 'category' => 'Taman', 'owner' => 'alif_taman', 'address' => 'Jl. Barito, Jakarta', 'description' => 'Taman teduh dekat Blok M dengan lintasan jalan kaki.', 'lat' => -6.2457, 'lng' => 106.7942],
        ['name' => 'Museum Bank Indonesia', 'category' => 'Museum', 'owner' => 'sari_budaya', 'address' => 'Jl. Pintu Besar Utara No. 3, Jakarta', 'description' => 'Museum interaktif tentang sejarah perbankan Indonesia.', 'lat' => -6.1371, 'lng' => 106.8132],
        ['name' => 'Hotel Keluarga Cawang', 'category' => 'Hotel', 'owner' => 'bayu_viewer', 'address' => 'Jl. MT Haryono No. 20, Jakarta', 'description' => 'Hotel sederhana dengan akses mudah ke transportasi umum.', 'lat' => -6.2439, 'lng' => 106.8721],
        ['name' => 'Tepi Danau Sunter', 'category' => 'Wisata Alam', 'owner' => 'raka_jalan', 'address' => 'Jl. Danau Sunter Selatan, Jakarta', 'description' => 'Area tepi danau untuk bersepeda dan melihat matahari sore.', 'lat' => -6.1467, 'lng' => 106.8586],
    ];

    public function run(): void
    {
        if (app()->environment('production') && ! $this->booleanEnv('PRODUCTION_DEMO_SEED_ENABLED')) {
            $this->command?->warn('ProductionDemoSeeder skipped because PRODUCTION_DEMO_SEED_ENABLED is not true.');

            return;
        }

        $password = $this->stringEnv('PRODUCTION_DEMO_PASSWORD');

        if (! $password) {
            throw new RuntimeException('PRODUCTION_DEMO_PASSWORD is required when production demo seeding is enabled.');
        }

        $this->seedDemoContent($password);
    }

    public function seedDemoContent(string $password): void
    {
        $this->call(ReferenceDataSeeder::class);

        $users = $this->seedUsers($password);
        $locations = $this->seedLocations($users);
        $posts = $this->seedPosts($users, $locations);

        $this->seedFollows($users);
        $this->seedEngagement($users, $posts);
    }

    private function seedUsers(string $password): array
    {
        $users = [];
        $passwordHash = Hash::make($password);

        foreach (self::USERS as $userData) {
            $username = $userData['username'];
            $avatarPath = $username === 'mediauser' ? null : "seed/avatars/{$username}.jpg";

            if ($avatarPath) {
                $this->putSeedAsset($avatarPath, "avatars/{$username}.jpg");
            }

            $user = $this->trackedModel(
                "production-demo:user:{$username}",
                User::class,
                fn () => User::where('username', $username)->first(),
            );
            $previousAvatarPath = $user->profile_picture;

            $user->forceFill([
                'name' => $userData['name'],
                'username' => $username,
                'email' => "{$username}@demo.bage.app",
                'password' => $passwordHash,
                'bio' => $userData['bio'],
                'profile_picture' => $avatarPath,
                'email_verified_at' => now(),
            ])->save();

            $this->deleteStaleSeedPath($previousAvatarPath, $avatarPath);

            $this->remember("production-demo:user:{$username}", $user);
            $users[$username] = $user->fresh();
        }

        return $users;
    }

    private function seedLocations(array $users): array
    {
        $categories = DB::table('categories')->pluck('id', 'name');
        $locations = [];

        foreach (self::LOCATIONS as $index => $locationData) {
            $key = sprintf('production-demo:location:%02d', $index + 1);
            $user = $users[$locationData['owner']];

            $payload = [
                'category_id' => $categories[$locationData['category']],
                'user_id' => $user->id,
                'name' => $locationData['name'],
                'address' => $locationData['address'],
                'description' => $locationData['description'],
                'created_at' => now()->subDays(120 - $index),
                'updated_at' => now()->subDays(60 - ($index % 30)),
            ];

            $location = $this->upsertLocation($key, $payload, (float) $locationData['lat'], (float) $locationData['lng']);
            $locations[] = $location;
        }

        return $locations;
    }

    private function seedPosts(array $users, array $locations): array
    {
        $usernames = array_keys($users);
        $posts = [];
        $contents = [
            'Tempat ini enak untuk singgah sore, suasananya rapi dan mudah dicapai.',
            'Pelayanan cukup cepat, cocok untuk ketemu teman setelah jam kerja.',
            'Saya suka detail kecil di tempat ini, terutama area duduk dan pencahayaan.',
            'Lokasinya strategis, tetapi tetap terasa santai untuk kunjungan pendek.',
            'Pilihan yang solid untuk akhir pekan tanpa perlu keluar jauh dari kota.',
            'Tempatnya bersih dan terasa nyaman untuk datang bersama keluarga.',
            'Ada beberapa sudut foto yang menarik dan tidak terlalu ramai saat pagi.',
            'Pengalaman singkat yang menyenangkan, kemungkinan besar akan kembali.',
        ];

        for ($i = 1; $i <= 60; $i++) {
            $author = $users[$usernames[($i - 1) % count($usernames)]];
            $locationIndex = ($i - 1) % count($locations);
            $location = $locations[$locationIndex];
            $locationCategory = self::LOCATIONS[$locationIndex]['category'];
            $key = sprintf('production-demo:post:%03d', $i);
            $content = $i % 15 === 0 ? null : $contents[$i % count($contents)];
            $rating = $i % 10 === 0 ? null : (($i % 5) + 1);

            $post = $this->trackedModel($key, Post::class);
            $post->forceFill([
                'user_id' => $author->id,
                'location_id' => $location->id,
                'content' => $content,
                'rating' => $rating,
                'media_type' => null,
                'media_url' => null,
                'created_at' => now()->subDays(90 - $i),
                'updated_at' => now()->subDays(45 - ($i % 30)),
            ])->save();

            $this->remember($key, $post);
            $this->syncPostMedia($post, $i, $locationCategory);
            $posts[$i] = $post->fresh();
        }

        return $posts;
    }

    private function seedFollows(array $users): void
    {
        $relationships = [
            'bayu_viewer' => ['raka_jalan', 'naya_kuliner', 'dimas_kopi', 'sari_budaya', 'alif_taman', 'mediauser'],
            'testuser' => ['raka_jalan', 'naya_kuliner', 'dimas_kopi'],
            'mira_notifikasi' => ['raka_jalan', 'sari_budaya', 'bayu_viewer'],
            'raka_jalan' => ['naya_kuliner', 'alif_taman', 'sari_budaya'],
            'naya_kuliner' => ['dimas_kopi', 'raka_jalan'],
            'dimas_kopi' => ['naya_kuliner', 'mediauser'],
            'sari_budaya' => ['raka_jalan', 'mira_notifikasi'],
            'alif_taman' => ['raka_jalan', 'bayu_viewer'],
            'mediauser' => ['dimas_kopi', 'naya_kuliner'],
        ];

        foreach ($relationships as $follower => $followings) {
            $followerUser = $users[$follower];
            $ids = collect($followings)->map(fn ($username) => $users[$username]->id)->all();
            $followerUser->followings()->syncWithoutDetaching($ids);

            foreach ($followings as $following) {
                $this->upsertNotification(
                    "production-demo:notification:follow:{$follower}:{$following}",
                    $users[$following]->id,
                    'follow',
                    [
                        'follower_id' => $followerUser->id,
                        'follower_username' => $followerUser->username,
                        'follower_profile_picture_url' => $followerUser->profile_picture ? url(Storage::disk('public')->url($followerUser->profile_picture)) : null,
                        'message' => "{$followerUser->username} mulai mengikuti anda",
                    ],
                    str_starts_with($following, 'mira') ? null : now()->subDays(2),
                );
            }
        }
    }

    private function seedEngagement(array $users, array $posts): void
    {
        $userList = array_values($users);

        foreach ($posts as $index => $post) {
            $likeTarget = 2 + ($index % 4);

            for ($i = 0; $i < $likeTarget; $i++) {
                $liker = $userList[($index + $i) % count($userList)];

                if ($liker->id === $post->user_id) {
                    continue;
                }

                $post->likes()->firstOrCreate(
                    ['user_id' => $liker->id],
                    ['created_at' => now()->subDays(30 - ($index % 30)), 'updated_at' => now()->subDays(15 - ($index % 15))],
                );

                if ($i === 0) {
                    $this->upsertNotification(
                        "production-demo:notification:like:{$post->id}:{$liker->id}",
                        $post->user_id,
                        'like',
                        [
                            'liker_username' => $liker->username,
                            'liker_profile_picture_url' => $liker->profile_picture ? url(Storage::disk('public')->url($liker->profile_picture)) : null,
                            'post_id' => $post->id,
                            'post_image' => $post->media()->value('media_url'),
                            'message' => 'menyukai unggahan anda',
                            'location_name' => $post->location->name,
                            'location_id' => $post->location_id,
                        ],
                        $index % 3 === 0 ? null : now()->subDay(),
                    );
                }
            }

            $commentCount = $index % 4 === 0 ? 2 : 1;
            for ($i = 0; $i < $commentCount; $i++) {
                $commenter = $userList[($index + $i + 3) % count($userList)];

                if ($commenter->id === $post->user_id) {
                    continue;
                }

                $comment = $this->upsertComment(
                    "production-demo:comment:{$post->id}:{$i}",
                    $post,
                    $commenter,
                    $this->commentText($index, $i),
                );

                $this->upsertNotification(
                    "production-demo:notification:comment:{$comment->id}",
                    $post->user_id,
                    'comment',
                    [
                        'commenter_username' => $commenter->username,
                        'commenter_profile_picture_url' => $commenter->profile_picture ? url(Storage::disk('public')->url($commenter->profile_picture)) : null,
                        'comment_content' => $comment->content,
                        'post_id' => $post->id,
                        'comment_id' => $comment->id,
                        'post_image' => $post->media()->value('media_url'),
                        'location_name' => $post->location->name,
                        'location_id' => $post->location_id,
                        'message' => 'mengomentari unggahan anda',
                    ],
                    $index % 5 === 0 ? null : now()->subHours(12),
                );
            }
        }
    }

    private function upsertLocation(string $key, array $payload, float $latitude, float $longitude): Location
    {
        $record = SeedRecord::where('key', $key)->first();
        $id = $record?->recordable_type === Location::class ? $record->recordable_id : null;

        if (DB::connection()->getDriverName() === 'sqlite') {
            $payload['coordinates'] = json_encode(['lat' => $latitude, 'lng' => $longitude], JSON_THROW_ON_ERROR);

            if ($id && DB::table('locations')->where('id', $id)->exists()) {
                DB::table('locations')->where('id', $id)->update($payload);
            } else {
                $existingId = DB::table('locations')->where('name', $payload['name'])->value('id');
                $id = $existingId ?: DB::table('locations')->insertGetId($payload);
            }

            $location = Location::withoutGlobalScopes()->findOrFail($id);
            $this->remember($key, $location);

            return $location;
        }

        $payload['coordinates'] = new Point($latitude, $longitude, 4326);
        $location = $id ? Location::find($id) : Location::where('name', $payload['name'])->first();
        $location ??= new Location;
        $location->forceFill($payload)->save();
        $this->remember($key, $location);

        return $location->fresh();
    }

    private function trackedModel(string $key, string $class, ?callable $fallback = null): Model
    {
        $record = SeedRecord::where('key', $key)->first();

        if ($record?->recordable_type === $class && $record->recordable_id) {
            $model = $class::find($record->recordable_id);

            if ($model) {
                return $model;
            }
        }

        if ($fallback) {
            $model = $fallback();

            if ($model) {
                return $model;
            }
        }

        return new $class;
    }

    private function remember(string $key, Model $model, array $meta = []): void
    {
        SeedRecord::updateOrCreate(
            ['key' => $key],
            [
                'recordable_type' => $model::class,
                'recordable_id' => $model->getKey(),
                'meta' => $meta,
            ],
        );
    }

    private function syncPostMedia(Post $post, int $postNumber, string $locationCategory): void
    {
        $mediaCount = match (true) {
            $postNumber % 7 === 0 => 4,
            $postNumber % 3 === 0 => 2,
            $postNumber % 5 === 0 => 0,
            default => 1,
        };

        $desiredPaths = [];

        for ($i = 1; $i <= $mediaCount; $i++) {
            $path = sprintf('seed/posts/post-%03d-%d.jpg', $postNumber, $i);
            $desiredPaths[] = $path;
            $this->putSeedAsset($path, $this->placeAssetPath($locationCategory, $postNumber + $i - 1));

            PostMedia::updateOrCreate(
                ['post_id' => $post->id, 'media_url' => $path],
                ['media_type' => 'image'],
            );
        }

        $existingSeedPaths = PostMedia::where('post_id', $post->id)
            ->where('media_url', 'like', sprintf('seed/posts/post-%03d-%%', $postNumber))
            ->pluck('media_url');

        $existingSeedPaths
            ->diff($desiredPaths)
            ->each(fn (string $path) => $this->deleteSeedStoragePath($path));

        PostMedia::where('post_id', $post->id)
            ->where('media_url', 'like', sprintf('seed/posts/post-%03d-%%', $postNumber))
            ->whereNotIn('media_url', $desiredPaths ?: ['__none__'])
            ->delete();
    }

    private function upsertComment(string $key, Post $post, User $commenter, string $content): Comment
    {
        $comment = $this->trackedModel($key, Comment::class);
        $comment->forceFill([
            'post_id' => $post->id,
            'user_id' => $commenter->id,
            'content' => $content,
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(5),
        ])->save();

        $this->remember($key, $comment);

        return $comment->fresh();
    }

    private function upsertNotification(string $key, int $userId, string $type, array $data, mixed $readAt): void
    {
        $notification = $this->trackedModel($key, Notification::class);
        $notification->forceFill([
            'user_id' => $userId,
            'type' => $type,
            'data' => $data,
            'read_at' => $readAt,
            'created_at' => now()->subDays(4),
            'updated_at' => now()->subDays(2),
        ])->save();

        $this->remember($key, $notification);
    }

    private function commentText(int $index, int $offset): string
    {
        $comments = [
            'Tempatnya terlihat nyaman, masuk daftar kunjungan saya.',
            'Setuju, aksesnya juga cukup mudah dari transportasi umum.',
            'Foto dan ulasannya membantu sekali.',
            'Saya pernah lewat sini, ternyata menarik juga.',
            'Cocok untuk akhir pekan singkat.',
        ];

        return $comments[($index + $offset) % count($comments)];
    }

    private function placeAssetPath(string $categoryName, int $index): string
    {
        $slug = self::CATEGORY_ASSET_SLUGS[$categoryName] ?? throw new RuntimeException("Missing seed asset mapping for category [{$categoryName}].");
        $number = (($index - 1) % 4) + 1;

        return sprintf('places/%s-%02d.jpg', $slug, $number);
    }

    private function putSeedAsset(string $storagePath, string $assetPath): void
    {
        $fullPath = self::ASSET_ROOT.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $assetPath);

        if (! is_file($fullPath)) {
            throw new RuntimeException("Demo seed asset [{$assetPath}] is missing.");
        }

        Storage::disk('public')->put($storagePath, file_get_contents($fullPath));
    }

    private function deleteStaleSeedPath(?string $previousPath, ?string $currentPath): void
    {
        if (! $previousPath || $previousPath === $currentPath) {
            return;
        }

        $this->deleteSeedStoragePath($previousPath);
    }

    private function deleteSeedStoragePath(string $path): void
    {
        if (! str_starts_with($path, 'seed/avatars/') && ! str_starts_with($path, 'seed/posts/')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    private function booleanEnv(string $key): bool
    {
        $value = getenv($key);

        if ($value === false) {
            $value = env($key, false);
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function stringEnv(string $key): ?string
    {
        $value = getenv($key);

        if ($value === false) {
            $value = env($key);
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
