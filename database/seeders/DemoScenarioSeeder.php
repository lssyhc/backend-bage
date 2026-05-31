<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoScenarioSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('DemoScenarioSeeder is disabled in production.');

            return;
        }

        $password = getenv('DEMO_SEED_PASSWORD') ?: env('DEMO_SEED_PASSWORD', 'Str0ng!DemoSeed123');

        (new ProductionDemoSeeder)->seedDemoContent($password);
    }
}
