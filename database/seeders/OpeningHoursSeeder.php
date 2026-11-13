<?php

namespace Database\Seeders;

use App\Models\OpeningHour;
use Illuminate\Database\Seeder;

class OpeningHoursSeeder extends Seeder
{
    public function run(): void
    {
        $morning = ['opens_at' => '09:30', 'closes_at' => '14:00'];
        $evening = ['opens_at' => '17:00', 'closes_at' => '21:00'];

        $week = [
            1 => [],
            2 => [$morning, $evening],
            3 => [$morning, $evening],
            4 => [$morning, $evening],
            5 => [$morning, $evening],
            6 => [$morning, $evening],
            7 => [$evening],
        ];

        foreach ($week as $weekday => $periods) {
            OpeningHour::query()->firstOrCreate(
                ['weekday' => $weekday],
                ['periods' => $periods],
            );
        }
    }
}