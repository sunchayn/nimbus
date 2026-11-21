<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Actions\ValidatePayloadAction;
use Sunchayn\Nimbus\Tests\App\Modules\Mcp\Concerns\FakesExtractedRoutes;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ValidatePayloadAction::class)]
class ValidatePayloadActionFunctionalTest extends TestCase
{
    use FakesExtractedRoutes;

    public function test_it_accepts_a_valid_payload(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        $action = resolve(ValidatePayloadAction::class);

        // Act

        $result = $action->execute(
            route: 'POST api/users',
            body: ['email' => 'ada@example.com', 'name' => 'Ada Lovelace'],
        );

        // Assert

        $this->assertTrue($result['valid']);

        $this->assertSame(
            [],
            $result['errors'],
        );
    }

    public function test_it_reports_validation_errors(): void
    {
        // Arrange

        $this->fakeExtractedRoutes();

        $action = resolve(ValidatePayloadAction::class);

        // Act

        $result = $action->execute(
            route: 'POST api/users',
            body: ['email' => 'nope'],
        );

        // Assert

        $this->assertFalse($result['valid']);

        $this->assertContains(
            'name',
            array_column($result['errors'], 'path'),
        );

        $this->assertContains(
            'email',
            array_column($result['errors'], 'path'),
        );
    }
}
