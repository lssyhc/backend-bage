<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Notification;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('DatabaseSeeder is disabled in production.');

            return;
        }

        // 1. Categories
        $this->call([
            CategorySeeder::class,
        ]);

        $this->command->info('Categories seeded.');

        // 2. Users (100)
        if (! User::where('username', 'testuser')->exists()) {
            User::factory()->create([
                'name' => 'Test User',
                'username' => 'testuser',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        $usersNeeded = 100 - User::count();
        if ($usersNeeded > 0) {
            User::factory($usersNeeded)->create();
        }

        // 3. User Profile Pictures & Follows
        $this->command->info('Updating User Profiles and Social Graph...');
        $users = User::all();
        $userBar = $this->command->getOutput()->createProgressBar($users->count());
        $userBar->start();

        foreach ($users as $user) {
            // Profile Picture (reuse existing logic)
            // 70% chance to have a profile picture
            if (! $user->profile_picture && rand(1, 100) <= 70) {
                try {
                    $profileUrl = 'https://picsum.photos/200/200';
                    $profileContent = Http::timeout(5)->get($profileUrl)->body(); // Lower timeout

                    if ($profileContent) {
                        $filename = 'profiles/'.Str::random(40).'.jpg';
                        Storage::disk('public')->put($filename, $profileContent);
                        $user->update(['profile_picture' => $filename]);
                    }
                } catch (\Exception $e) {
                    // Ignore
                }
            }

            // Social Graph: Follow 5-20 random users
            $usersToFollow = $users->where('id', '!=', $user->id)->random(rand(5, 20));
            $user->followings()->syncWithoutDetaching($usersToFollow->pluck('id'));

            // Create Follow Notifications
            foreach ($usersToFollow as $targetUser) {
                Notification::create([
                    'user_id' => $targetUser->id,
                    'type' => 'follow',
                    'data' => [
                        'follower_id' => $user->id,
                        'follower_username' => $user->username,
                        'follower_profile_picture_url' => $user->profile_picture ? url(Storage::disk('public')->url($user->profile_picture)) : null,
                    ],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $userBar->advance();
        }
        $userBar->finish();
        $this->command->newLine();
        $this->command->info('User profiles and follows seeded.');

        // 4. Locations (100)
        Location::factory(100)->create();
        $this->command->info('Locations seeded.');

        // 5. Posts (100) with Media & Interactions
        $this->command->info('Seeding Posts, Media, and Interactions...');

        $bar = $this->command->getOutput()->createProgressBar(100);
        $bar->start();

        for ($i = 0; $i < 100; $i++) {
            $post = Post::factory()->create();
            $post->load('location'); // Ensure location is loaded if set by factory

            // Media
            $imageCount = rand(1, 4);
            $firstMediaUrl = null;
            for ($j = 0; $j < $imageCount; $j++) {
                try {
                    $imageUrl = 'https://picsum.photos/640/480';
                    $imageContent = Http::timeout(5)->get($imageUrl)->body();

                    if ($imageContent) {
                        $filename = 'posts/'.Str::random(40).'.jpg';
                        Storage::disk('public')->put($filename, $imageContent);

                        $media = $post->media()->create([
                            'media_url' => $filename,
                            'media_type' => 'image',
                        ]);
                        if (! $firstMediaUrl) {
                            $firstMediaUrl = $media->media_url; // Assuming model accessor or just path
                        }
                    }
                } catch (\Exception $e) {
                    // Ignore
                }
            }

            // Interactions (Likes & Comments)
            // Virality factor: 0-100.
            // 80% posts get 0-20 likes. 15% get 20-50. 5% get 50-100.
            $virality = rand(1, 100);
            if ($virality > 95) {
                $likeCount = rand(50, 100);
            } elseif ($virality > 80) {
                $likeCount = rand(20, 50);
            } else {
                $likeCount = rand(0, 20);
            }

            // Create Likes
            // Pick random users to like
            if ($likeCount > 0) {
                $likers = $users->random(min($likeCount, $users->count()));
                foreach ($likers as $liker) {
                    // Time of like: random time between post creation and now
                    $createdAt = fake()->dateTimeBetween($post->created_at, 'now');

                    $post->likes()->create([
                        'user_id' => $liker->id,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);

                    // Create Like Notification
                    if ($liker->id !== $post->user_id) {
                        Notification::create([
                            'user_id' => $post->user_id,
                            'type' => 'like',
                            'data' => [
                                'liker_username' => $liker->username,
                                'liker_profile_picture_url' => $liker->profile_picture ? url(Storage::disk('public')->url($liker->profile_picture)) : null,
                                'post_id' => $post->id,
                                'post_image' => $firstMediaUrl, // Pass first media URL
                                'message' => 'menyukai unggahan anda',
                                'location_name' => $post->location?->name,
                                'location_id' => $post->location_id,
                            ],
                            'created_at' => $createdAt,
                            'updated_at' => $createdAt,
                        ]);
                    }
                }
            }

            // Create Comments (approx 20% of likes count)
            $commentCount = floor($likeCount * 0.2);
            if ($commentCount > 0) {
                $commenters = $users->random(min($commentCount, $users->count()));
                foreach ($commenters as $commenter) {
                    $createdAt = fake()->dateTimeBetween($post->created_at, 'now');
                    $content = fake()->randomElement([
                        'Keren banget!',
                        'Mantap!',
                        'Nice shot!',
                        'Wow...',
                        'Sangat menginspirasi',
                        'Boleh juga',
                        'Looking good',
                        'Amazing!',
                        'Wonderful place',
                        'Gas kesana',
                    ]);

                    $comment = $post->comments()->create([
                        'user_id' => $commenter->id,
                        'content' => $content,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);

                    // Create Comment Notification
                    if ($commenter->id !== $post->user_id) {
                        Notification::create([
                            'user_id' => $post->user_id,
                            'type' => 'comment',
                            'data' => [
                                'commenter_username' => $commenter->username,
                                'commenter_profile_picture_url' => $commenter->profile_picture ? url(Storage::disk('public')->url($commenter->profile_picture)) : null,
                                'comment_content' => $content,
                                'post_id' => $post->id,
                                'comment_id' => $comment->id,
                                'post_image' => $firstMediaUrl,
                                'location_name' => $post->location?->name,
                                'location_id' => $post->location_id,
                            ],
                            'created_at' => $createdAt,
                            'updated_at' => $createdAt,
                        ]);
                    }
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info('Posts and interactions seeded.');
    }
}
