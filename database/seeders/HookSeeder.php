<?php

namespace Database\Seeders;

use App\Models\Hook;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hooks = collect(config('hooks'))->map(function ($hook) {
            return array_merge($hook, [
                'description' => str_replace('\n', "\n", trim($hook['description'])),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        })->toArray();

        Hook::upsert(
            $hooks,
            ['name'],
            ['description']
        );
    }
}
