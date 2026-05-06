<?php

namespace Database\Seeders;

use App\Models\Hook;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FreeHookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (config('free-hooks') as $hook) {
            Hook::updateOrCreate(
                [
                    'slug' => Str::slug($hook['name']),
                ],
                [
                    'name' => $hook['name'],
                    'description' => str_replace('\n', "\n", trim($hook['description'])),
                    'access_level' => 'free'
                ]
            );
        }
    }
}
