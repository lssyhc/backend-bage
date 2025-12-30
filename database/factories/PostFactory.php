<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->first()->id ?? User::factory(),
            'location_id' => Location::inRandomOrder()->first()->id ?? Location::factory(),
            'content' => fake()->paragraph(),
            'rating' => fake()->optional(0.7)->numberBetween(1, 5), // 70% chance of having a rating
            'created_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
