<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use MatanYadaev\EloquentSpatial\Objects\Point;

class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        // Jakarta coordinates (approximate bounds)
        // Lat: -6.37 to -6.08
        // Lng: 106.68 to 106.97
        $latitude = fake()->latitude(-6.30, -6.10);
        $longitude = fake()->longitude(106.70, 106.90);

        return [
            'category_id' => Category::inRandomOrder()->first()->id ?? Category::factory(),
            'user_id' => User::inRandomOrder()->first()->id ?? User::factory(),
            'name' => Str::limit(fake()->company(), 50),
            'address' => Str::limit(fake()->streetAddress() . ', Jakarta', 100),
            'description' => Str::limit(fake()->paragraph(), 140),
            'coordinates' => new Point($latitude, $longitude),
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
