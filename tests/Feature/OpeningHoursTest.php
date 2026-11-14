<?php

namespace Tests\Feature;

use App\Models\OpeningHour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OpeningHoursTest extends TestCase
{
    use RefreshDatabase;

    private const ORIGINAL_PERIODS = [
        ['opens_at' => '09:30', 'closes_at' => '14:00'],
    ];

    protected function beforeRefreshingDatabase(): void
    {
        $connection = DB::connection();

        $this->assertSame(
            ['sqlite', ':memory:'],
            [$connection->getDriverName(), $connection->getDatabaseName()],
            'I test richiedono SQLite in memoria.',
        );
    }

    public function test_collaborator_can_update_the_public_opening_hours(): void
    {
        $day = $this->createTuesday();
        $collaborator = User::factory()->create(['role' => 'collaborator']);

        $periods = [
            ['opens_at' => '10:00', 'closes_at' => '14:00'],
            ['opens_at' => '17:00', 'closes_at' => '21:00'],
        ];

        $this->actingAs($collaborator, 'web')
            ->putJson("/api/staff/opening-hours/{$day->weekday}", [
                'periods' => $periods,
            ])
            ->assertOk();

        $this->getJson('/api/opening-hours')
            ->assertOk()
            ->assertJsonPath('0.weekday', 2)
            ->assertJsonPath('0.periods', $periods);
    }

    public function test_overlapping_periods_leave_the_saved_hours_unchanged(): void
    {
        $day = $this->createTuesday();
        $collaborator = User::factory()->create(['role' => 'collaborator']);

        $this->actingAs($collaborator, 'web')
            ->putJson("/api/staff/opening-hours/{$day->weekday}", [
                'periods' => [
                    ['opens_at' => '09:30', 'closes_at' => '14:00'],
                    ['opens_at' => '13:00', 'closes_at' => '17:00'],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['periods']);

        $this->assertSame(self::ORIGINAL_PERIODS, $day->refresh()->periods);
    }

    private function createTuesday(): OpeningHour
    {
        $day = new OpeningHour();
        $day->weekday = 2;
        $day->periods = self::ORIGINAL_PERIODS;
        $day->save();

        return $day;
    }
}