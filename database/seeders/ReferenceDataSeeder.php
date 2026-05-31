<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class ReferenceDataSeeder extends Seeder
{
    public const CATEGORIES = [
        ['name' => 'Kafe', 'icon' => 'coffee'],
        ['name' => 'Restoran', 'icon' => 'utensils'],
        ['name' => 'Taman', 'icon' => 'tree'],
        ['name' => 'Museum', 'icon' => 'landmark'],
        ['name' => 'Hotel', 'icon' => 'hotel'],
        ['name' => 'Wisata Alam', 'icon' => 'mountain'],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                ['icon' => $category['icon']],
            );
        }
    }
}
