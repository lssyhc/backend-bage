<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Kafe', 'icon' => 'coffee'],
            ['name' => 'Restoran', 'icon' => 'utensils'],
            ['name' => 'Taman', 'icon' => 'tree'],
            ['name' => 'Museum', 'icon' => 'landmark'],
            ['name' => 'Hotel', 'icon' => 'hotel'],
            ['name' => 'Wisata Alam', 'icon' => 'mountain'],
        ];

        foreach ($categories as $cat) {
            Category::create([
                'name' => $cat['name'],
                'slug' => Str::slug($cat['name']),
                'icon' => $cat['icon'],
            ]);
        }
    }
}
