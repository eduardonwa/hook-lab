<?php

namespace App\Services;

use App\Models\Cycle;
use Illuminate\Support\Str;

class CycleNameGenerator
{
    public static function generate(): string
    {
        $adjectives = config('cycle_names.adjectives');
        $nouns = config('cycle_names.nouns');

        do {
            $adj = $adjectives[array_rand($adjectives)];
            $noun = $nouns[array_rand($nouns)];
        } while (Str::lower($adj) === Str::lower($noun));

        return Str::title("$adj $noun");
    }
    
    public static function generateUnique(): string
    {
        do {
            $name = self::generate();
        } while (Cycle::where('name', $name)->exists());
        
        return $name;
    }
}