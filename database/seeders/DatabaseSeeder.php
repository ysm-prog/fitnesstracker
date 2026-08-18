<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Only the shared exercise library. No demo user: this seeder runs against
     * production, and inventing accounts there is how test credentials end up
     * live.
     */
    public function run(): void
    {
        $this->call(SystemExerciseSeeder::class);
    }
}
