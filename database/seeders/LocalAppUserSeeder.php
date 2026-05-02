<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LocalAppUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'app@hooklab.local'],
            [
                'name' => 'App',
                'password' => Hash::make(env('LOCAL_APP_USER_PASSWORD', 'hooklab-local-dev')),
                'email_verified_at' => now()
            ]
        );
    }
}
