<?php

namespace Database\Seeders;

use App\Models\Trigger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TriggerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $triggers = collect(config('triggers'))->map(function ($trigger) {
            return [
                'key' => $trigger['key'],
                'name' => $trigger['name'],
                'slug' => Str::slug($trigger['name']),
                'description' => str_replace('\n', "\n", trim($trigger['description'])),
                'access_level' => $trigger['access_level'],
                'sort_order' => $trigger['sort_order'] ?? 0,
                'is_active' => $trigger['is_active'] ?? true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        Trigger::upsert(
            $triggers,
            ['key'],
            [
                'name',
                'slug',
                'description',
                'access_level',
                'updated_at',
            ]
        );
    }
}