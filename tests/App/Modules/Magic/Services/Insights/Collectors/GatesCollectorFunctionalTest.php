<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Services\Insights\Collectors;

use Illuminate\Auth\Access\Response;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\Collectors\GatesCollector;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(GatesCollector::class)]
class GatesCollectorFunctionalTest extends TestCase
{
    public function test_it_collects_gates_with_argument_class_names(): void
    {
        // Arrange

        Gate::define('update', fn (?Authenticatable $user): bool => false);

        $collector = resolve(GatesCollector::class);
        $collector->start();

        // Act

        Gate::check('update', [new \stdClass, 5]);

        $result = $collector->collect();

        // Assert

        $this->assertSame(
            [['ability' => 'update', 'result' => false, 'arguments' => ['stdClass', 'int'], 'user' => null]],
            $result->gates,
        );
    }

    public function test_it_collects_the_identifier_of_the_user_who_was_checked(): void
    {
        // Arrange

        Gate::define('view', fn (Authenticatable $user): bool => true);

        $collector = resolve(GatesCollector::class);
        $collector->start();

        // Act

        Gate::forUser(new GenericUser(['id' => 42]))->check('view');

        $result = $collector->collect();

        // Assert

        $this->assertSame(
            [['ability' => 'view', 'result' => true, 'arguments' => [], 'user' => '42']],
            $result->gates,
        );
    }

    public function test_it_records_every_check_in_order(): void
    {
        // Arrange

        Gate::define('first', fn (?Authenticatable $user): bool => true);
        Gate::define('second', fn (?Authenticatable $user): bool => false);

        $collector = resolve(GatesCollector::class);
        $collector->start();

        // Act

        Gate::check('first');
        Gate::check('second');

        $result = $collector->collect();

        // Assert

        $this->assertSame(['first', 'second'], array_column($result->gates, 'ability'));
        $this->assertSame([true, false], array_column($result->gates, 'result'));
    }

    public function test_it_records_no_result_when_the_gate_does_not_return_a_boolean(): void
    {
        // Arrange

        Gate::define('inspect', fn (?Authenticatable $user): Response => Response::allow());

        $collector = resolve(GatesCollector::class);
        $collector->start();

        // Act

        Gate::inspect('inspect');

        $result = $collector->collect();

        // Assert

        $this->assertCount(1, $result->gates);
        $this->assertNull($result->gates[0]['result']);
    }

    public function test_it_does_not_change_the_outcome_of_a_check(): void
    {
        // Arrange

        Gate::define('update', fn (?Authenticatable $user): bool => false);

        $collector = resolve(GatesCollector::class);
        $collector->start();

        // Act

        $allowed = Gate::allows('update');

        // Assert

        $this->assertFalse($allowed);
    }
}
