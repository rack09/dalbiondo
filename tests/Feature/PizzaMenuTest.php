<?php

namespace Tests\Feature;

use App\Models\Pizza;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PizzaMenuTest extends TestCase
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

    public function test_public_menu_lists_only_visible_pizzas(): void
    {
        $visiblePizza = $this->createPizza('Margherita');
        $this->createPizza('Diavola', visible: false);

        $this->getJson('/api/pizzas')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $visiblePizza->id)
            ->assertJsonPath('0.name', 'Margherita');
    }

    public function test_collaborator_can_hide_and_show_a_house_pizza(): void
    {
        $pizza = $this->createPizza('Dal Biondo');
        $pizza->is_featured = true;
        $pizza->is_house_pizza = true;
        $pizza->save();

        $collaborator = User::factory()->create(['role' => 'collaborator']);
        $url = "/api/staff/pizzas/{$pizza->id}";

        $this->actingAs($collaborator, 'web')
            ->patchJson($url, ['is_visible' => false])
            ->assertOk();

        $this->assertDatabaseHas('pizzas', [
            'id' => $pizza->id,
            'is_visible' => false,
            'is_featured' => false,
            'is_house_pizza' => false,
        ]);

        $this->getJson('/api/pizzas')
            ->assertOk()
            ->assertJsonCount(0);

        $this->patchJson($url, ['is_visible' => true])->assertOk();

        $this->getJson('/api/pizzas')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $pizza->id)
            ->assertJsonPath('0.is_featured', false)
            ->assertJsonPath('0.is_house_pizza', false);
    }

    public function test_owner_cannot_choose_a_seventh_featured_pizza_as_house_pizza(): void
    {
        [$currentHouse] = $this->createSixFeaturedPizzas();
        $candidate = $this->createPizza('Dal Biondo Master');
        $owner = User::factory()->create(['role' => 'owner']);

        $this->actingAs($owner, 'web')
            ->patchJson("/api/staff/pizzas/{$candidate->id}/featured", [
                'is_featured' => true,
                'is_house_pizza' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_featured']);

        $this->assertDatabaseHas('pizzas', [
            'id' => $candidate->id,
            'is_featured' => false,
            'is_house_pizza' => false,
        ]);

        $this->assertTrue($currentHouse->refresh()->is_house_pizza);
    }

    public function test_owner_can_choose_an_already_featured_pizza_at_the_limit(): void
    {
        [$currentHouse, $candidate] = $this->createSixFeaturedPizzas();
        $owner = User::factory()->create(['role' => 'owner']);

        $this->actingAs($owner, 'web')
            ->patchJson("/api/staff/pizzas/{$candidate->id}/featured", [
                'is_featured' => true,
                'is_house_pizza' => true,
            ])
            ->assertOk();

        $this->assertTrue($candidate->refresh()->is_house_pizza);

        $this->assertDatabaseHas('pizzas', [
            'id' => $currentHouse->id,
            'is_featured' => true,
            'is_house_pizza' => false,
        ]);

        $this->assertSame(6, Pizza::query()->where('is_featured', true)->count());
        $this->assertSame(1, Pizza::query()->where('is_house_pizza', true)->count());
    }

    public function test_collaborator_cannot_choose_the_house_pizza(): void
    {
        $pizza = $this->createPizza('Dal Biondo');
        $collaborator = User::factory()->create(['role' => 'collaborator']);

        $this->actingAs($collaborator, 'web')
            ->patchJson("/api/staff/pizzas/{$pizza->id}/featured", [
                'is_featured' => true,
                'is_house_pizza' => true,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('pizzas', [
            'id' => $pizza->id,
            'is_featured' => false,
            'is_house_pizza' => false,
        ]);
    }

    private function createSixFeaturedPizzas(): array
    {
        $pizzas = [];

        for ($number = 1; $number <= 6; $number++) {
            $pizza = $this->createPizza("Pizza di prova {$number}");
            $pizza->is_featured = true;
            $pizza->is_house_pizza = $number === 1;
            $pizza->save();

            $pizzas[] = $pizza;
        }

        return $pizzas;
    }

    private function createPizza(string $name, bool $visible = true): Pizza
    {
        return Pizza::query()->create([
            'name' => $name,
            'ingredients' => 'Pomodoro e mozzarella.',
            'price_trancio' => '6.00',
            'price_teglia' => '36.00',
            'is_visible' => $visible,
        ]);
    }
}
