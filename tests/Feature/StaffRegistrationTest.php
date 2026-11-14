<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

use App\Models\User;

class StaffRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = 'collaboratore@example.test';

    protected function beforeRefreshingDatabase(): void
    {
        $connection = DB::connection();

        $this->assertSame(
            ['sqlite', ':memory:'],
            [$connection->getDriverName(), $connection->getDatabaseName()],
            'I test richiedono SQLite in memoria.',
        );
    }

    public function test_valid_invitation_creates_a_collaborator(): void
    {
        $data = $this->registrationDataForNewInvitation();

        $this->postJson('/api/staff/registrations', $data)->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => self::EMAIL,
            'name' => $data['name'],
            'role' => 'collaborator',
        ]);

        $acceptedAt = DB::table('staff_invitations')
            ->where('email', self::EMAIL)
            ->value('accepted_at');

        $this->assertNotNull($acceptedAt);
    }

    public function test_used_invitation_cannot_be_reused(): void
    {
        $data = $this->registrationDataForNewInvitation();

        $this->postJson('/api/staff/registrations', $data)->assertCreated();
        $this->postJson('/api/staff/registrations', $data)->assertClientError();

        $this->assertDatabaseCount('users', 1);
    }

    public function test_collaborator_cannot_create_staff_invitations(): void
    {
        $collaborator = User::factory()->create(['role' => 'collaborator']);

        $this->actingAs($collaborator, 'web')
            ->postJson('/api/staff/invitations', ['email' => self::EMAIL])
            ->assertForbidden();

        $this->assertDatabaseMissing('staff_invitations', [
            'email' => self::EMAIL,
        ]);
    }

    private function registrationDataForNewInvitation(): array
    {
        $token = bin2hex(random_bytes(32));
        $password = 'DalBiondo-test-123!';

        DB::table('staff_invitations')->insert([
            'email' => self::EMAIL,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addHours(48),
            'accepted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'token' => $token,
            'name' => 'Collaboratore di prova',
            'password' => $password,
            'password_confirmation' => $password,
        ];
    }
}
