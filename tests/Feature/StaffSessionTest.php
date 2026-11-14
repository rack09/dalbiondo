<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        $connection = DB::connection();

        $this->assertSame(
            ['sqlite', ':memory:'],
            [$connection->getDriverName(), $connection->getDatabaseName()],
            'I test richiedono SQLite in memoria.',
        );
    }

    public function test_guest_cannot_access_the_staff_menu(): void
    {
        $this->getJson('/api/staff/pizzas')->assertUnauthorized();
    }

    public function test_owner_can_log_in(): void
    {
        $password = 'DalBiondo-test-123!';
        $owner = User::factory()->create([
            'role' => 'owner',
            'password' => Hash::make($password),
        ]);

        $this->postJson('/api/session', [
            'email' => $owner->email,
            'password' => $password,
        ])
            ->assertOk()
            ->assertJsonPath('user.role', 'owner');

        $this->assertAuthenticatedAs($owner, 'web');
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('DalBiondo-test-123!'),
        ]);

        $this->postJson('/api/session', [
            'email' => $user->email,
            'password' => 'Password-errata-123!',
        ])->assertUnprocessable();

        $this->assertGuest('web');
    }

    public function test_staff_can_log_out(): void
    {
        $user = User::factory()->create(['role' => 'collaborator']);

        $this->actingAs($user, 'web')
            ->deleteJson('/api/session')
            ->assertNoContent();

        $this->assertGuest('web');
    }
}
