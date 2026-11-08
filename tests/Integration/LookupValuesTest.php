<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Http\Api\Magic\LookupValuesController;
use Sunchayn\Nimbus\Http\Api\Magic\LookupValuesRequest;
use Sunchayn\Nimbus\Modules\Magic\Actions\ReadLookupValuesAction;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(LookupValuesController::class)]
#[CoversClass(LookupValuesRequest::class)]
#[CoversClass(ReadLookupValuesAction::class)]
class LookupValuesTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        /** @var Router $router */
        $router->prefix('demo')->group(__DIR__.'/../../workbench/routes/demo.php');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email');
            $table->string('password');
        });

        DB::table('users')->insert(
            collect(range(1, 25))->map(fn (int $id): array => ['email' => "u{$id}@test.dev", 'password' => 'secret'])->all(),
        );
    }

    public function test_it_reads_a_capped_list_for_an_extracted_exists_rule(): void
    {
        // Act

        $response = $this->lookup([['table' => 'users', 'column' => 'id']]);

        // Assert

        $response->assertOk();

        $this->assertCount(ReadLookupValuesAction::MAX_ROWS, $response->json('values')['users.id']);
        $this->assertSame(25, $response->json('values')['users.id'][0]);
    }

    public function test_it_refuses_a_pair_that_is_not_an_exists_rule_of_the_route(): void
    {
        // Act

        $response = $this->lookup([
            ['table' => 'users', 'column' => 'email'],
            ['table' => 'orders', 'column' => 'id'],
        ]);

        // Assert

        $response->assertOk();

        $this->assertSame([], $response->json('values'));
    }

    public function test_it_returns_an_empty_list_for_a_missing_table(): void
    {
        // Act

        $values = (new ReadLookupValuesAction)->execute(
            allowed: [['table' => 'ghosts', 'column' => 'id']],
            requested: [['table' => 'ghosts', 'column' => 'id']],
        );

        // Assert

        $this->assertSame(['ghosts.id' => []], $values);
    }

    public function test_it_refuses_unsafe_identifiers(): void
    {
        // Act

        $values = (new ReadLookupValuesAction)->execute(
            allowed: [['table' => 'users; drop', 'column' => 'id']],
            requested: [['table' => 'users; drop', 'column' => 'id']],
        );

        // Assert

        $this->assertSame([], $values);
    }

    /*
     * Helpers.
     */

    /**
     * @param  array<int, array{table: string, column: string}>  $pairs
     */
    private function lookup(array $pairs, string $endpoint = 'demo/shapes/rule-coverage'): \Illuminate\Testing\TestResponse
    {
        return $this->postJson(route('nimbus.api.magic.lookup-values'), [
            'method' => 'POST',
            'endpoint' => $endpoint,
            'pairs' => $pairs,
        ]);
    }
}
