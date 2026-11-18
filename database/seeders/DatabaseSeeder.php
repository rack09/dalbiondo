<?php

namespace Database\Seeders;

use App\Models\Pizza;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Cache::lock('dal-biondo:seed', 60)->block(10, function (): void {
            if (Pizza::query()->doesntExist()) {
                $this->call(DalBiondoPizzaSeeder::class);
            }

            $this->call(OpeningHoursSeeder::class);
        });
    }
}
