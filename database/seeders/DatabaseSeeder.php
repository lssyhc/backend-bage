<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Categories
        $this->call([
            CategorySeeder::class,
        ]);

        $this->command->info('Categories seeded.');

        // 2. Users (100)
        // Check if we need to create the test user separately or just include it
        if (!User::where('username', 'testuser')->exists()) {
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
        $this->command->info('Users seeded.');

        // 3. User Profile Pictures
        $this->command->info('Updating User Profile Pictures (this may take a while)...');
        $users = User::all();
        $userBar = $this->command->getOutput()->createProgressBar($users->count());
        $userBar->start();

        foreach ($users as $user) {
            try {
                $profileUrl = 'https://picsum.photos/200/200';
                $profileContent = Http::timeout(10)->get($profileUrl)->body();

                if ($profileContent) {
                    $filename = 'profiles/' . Str::random(40) . '.jpg';
                    Storage::disk('public')->put($filename, $profileContent);
                    $user->update(['profile_picture' => $filename]);
                }
            } catch (\Exception $e) {
                // Ignore
            }
            $userBar->advance();
        }
        $userBar->finish();
        $this->command->newLine();
        $this->command->info('User profiles seeded.');


        // 4. Locations (100)
        Location::factory(100)->create();
        $this->command->info('Locations seeded.');

        // 5. Posts (100) with Media
        // We will create posts and attach media to them.
        // Downloading 100 images might take time, so we'll do it in a loop with progress bar.

        $this->command->info('Seeding Posts and downloading media (this may take a while)...');

        $bar = $this->command->getOutput()->createProgressBar(100);
        $bar->start();

        // Create posts one by one to handle media attachment
        for ($i = 0; $i < 100; $i++) {
            $post = Post::factory()->create();

            // Randomize number of images (1-4)
            $imageCount = rand(1, 4);

            for ($j = 0; $j < $imageCount; $j++) {
                try {
                    // Download image
                    $imageUrl = 'https://picsum.photos/640/480';
                    $imageContent = Http::timeout(10)->get($imageUrl)->body();

                    if ($imageContent) {
                        $filename = 'posts/' . Str::random(40) . '.jpg';
                        Storage::disk('public')->put($filename, $imageContent);

                        $post->media()->create([
                            'media_url' => $filename,
                            'media_type' => 'image',
                        ]);
                    }
                } catch (\Exception $e) {
                    // Ignore download failures, just continue
                    // $this->command->error("Failed to download media for post {$post->id}: " . $e->getMessage());
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info('Posts seeded.');
    }
}
