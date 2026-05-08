<?php

namespace Database\Seeders;

use App\Models\Hook;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hooks = collect(config('hooks'))->map(function ($hook) {
            return [
                'key' => $hook['key'],
                'name' => $hook['name'],
                'slug' => Str::slug($hook['name']),
                'description' => str_replace('\n', "\n", trim($hook['description'])),
                'access_level' => $hook['access_level'],
                'user_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        Hook::upsert(
            $hooks,
            ['key'],
            [
                'name',
                'slug',
                'description',
                'access_level',
                'user_id',
                'updated_at',
            ]
        );
    }
}