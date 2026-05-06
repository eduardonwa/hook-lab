<?php

namespace Database\Seeders;

use App\Models\Hook;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            return array_merge($hook, [
                'name' => $hook['name'],
                'slug' => Str::slug($hook['name']),
                'description' => str_replace('\n', "\n", trim($hook['description'])),
                'access_level' => 'pro',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        })->toArray();

        Hook::upsert(
            $hooks,
            ['slug'],
            [
                'name',
                'description',
                'access_level',
                'updated_at'
            ]
        );
    }
}
