<?php

namespace Database\Seeders;

use Database\Seeders\FreeUserSeeder;
use Database\Seeders\TriggerSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            FreeUserSeeder::class,
            TriggerSeeder::class
        ]);
    }
}
