<?php

namespace Sunchayn\Nimbus\Tests\Integration\Actors;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Http\Api\Actors\ListActorsController;
use Sunchayn\Nimbus\Http\Api\Actors\ListActorsRequest;
use Sunchayn\Nimbus\Modules\Magic\Actions\ListActorsAction;
use Sunchayn\Nimbus\Tests\TestCase;

class ListedUser extends User
{
    protected $table = 'listed_users';

    protected $guarded = [];

    public $timestamps = false;
}

#[CoversClass(ListActorsController::class)]
#[CoversClass(ListActorsRequest::class)]
#[CoversClass(ListActorsAction::class)]
class ListActorsTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', ['driver' => 'sqlite', 'database' => ':memory:']);
        $app['config']->set('auth.providers.users.model', ListedUser::class);
        $app['config']->set('auth.guards.api', ['driver' => 'session', 'provider' => 'users']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('listed_users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
        });

        ListedUser::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'secret']);
        ListedUser::create(['name' => null, 'email' => 'grace@example.com', 'password' => 'secret']);
        ListedUser::create(['name' => null, 'email' => null, 'password' => 'secret']);
    }

    public function test_it_lists_actors_with_a_label_the_key_and_the_model(): void
    {
        // Act

        $response = $this->getJson(route('nimbus.api.actors'));

        // Assert

        $response->assertOk()
            ->assertJsonPath('guard', 'web')
            ->assertJsonPath('actors.0', ['id' => '1', 'label' => 'Ada Lovelace', 'model' => ListedUser::class])
            ->assertJsonPath('actors.1.label', 'grace@example.com')
            ->assertJsonPath('actors.2.label', '3');

        $this->assertContains('api', $response->json('guards'));
        $this->assertStringNotContainsString('secret', $response->getContent());
    }

    public function test_it_searches_by_name_or_email(): void
    {
        $this->getJson(route('nimbus.api.actors', ['q' => 'grace']))
            ->assertOk()
            ->assertJsonCount(1, 'actors')
            ->assertJsonPath('actors.0.id', '2');
    }

    public function test_it_searches_by_id_when_an_id_is_typed(): void
    {
        $this->getJson(route('nimbus.api.actors', ['q' => '3']))
            ->assertOk()
            ->assertJsonCount(1, 'actors')
            ->assertJsonPath('actors.0.id', '3');
    }

    public function test_it_caps_the_result(): void
    {
        // Arrange

        foreach (range(1, 25) as $i) {
            ListedUser::create(['name' => 'Bulk '.$i, 'email' => null, 'password' => 'x']);
        }

        // Act & Assert

        $this->getJson(route('nimbus.api.actors', ['q' => 'Bulk']))
            ->assertOk()
            ->assertJsonCount(ListActorsAction::LIMIT, 'actors');
    }

    public function test_it_uses_the_requested_guard(): void
    {
        $this->getJson(route('nimbus.api.actors', ['guard' => 'api']))
            ->assertOk()
            ->assertJsonPath('guard', 'api')
            ->assertJsonCount(3, 'actors');
    }

    public function test_it_rejects_an_unknown_guard(): void
    {
        $this->getJson(route('nimbus.api.actors', ['guard' => 'nope']))->assertUnprocessable();
    }
}
