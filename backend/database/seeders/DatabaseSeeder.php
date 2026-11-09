<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\M00Core\Database\Seeders\M00CoreSeeder;
use Modules\M01Fleet\Database\Seeders\M01FleetSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            M00CoreSeeder::class,
            M01FleetSeeder::class,
        ]);
    }
}
