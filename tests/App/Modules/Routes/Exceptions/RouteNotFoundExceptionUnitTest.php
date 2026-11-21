<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Exceptions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Routes\Exceptions\RouteNotFoundException;

#[CoversClass(RouteNotFoundException::class)]
class RouteNotFoundExceptionUnitTest extends TestCase
{
    public function test_it_names_the_reference_in_its_message(): void
    {
        // Act

        $exception = RouteNotFoundException::becauseNoRouteMatches(reference: 'GET nowhere');

        // Assert

        $this->assertSame(
            'No route matches `GET nowhere`.',
            $exception->getMessage(),
        );

        $this->assertSame(
            'GET nowhere',
            $exception->reference,
        );
    }

    public function test_it_has_no_suggestions_by_default(): void
    {
        // Act

        $exception = RouteNotFoundException::becauseNoRouteMatches(reference: 'GET nowhere');

        // Assert

        $this->assertSame(
            [],
            $exception->suggestions,
        );
    }

    public function test_it_keeps_the_suggestions(): void
    {
        // Act

        $exception = RouteNotFoundException::becauseNoRouteMatches(
            reference: 'POST api/user',
            suggestions: ['POST api/users'],
        );

        // Assert

        $this->assertSame(
            ['POST api/users'],
            $exception->suggestions,
        );
    }
}
