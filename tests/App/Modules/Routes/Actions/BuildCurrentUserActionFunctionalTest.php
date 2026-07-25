<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Routes\Actions\BuildCurrentUserAction;
use Sunchayn\Nimbus\Tests\App\Modules\Relay\Services\Authorization\Handlers\Stubs\DummyAuthenticatable;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(BuildCurrentUserAction::class)]
class BuildCurrentUserActionFunctionalTest extends TestCase
{
    public function test_it_builds_current_user(): void
    {
        // Arrange

        $user = new DummyAuthenticatable($id = fake()->randomNumber());

        auth()->setUser($user);

        $action = resolve(BuildCurrentUserAction::class);

        // Act

        $metadata = $action->execute();

        // Assert

        $this->assertEquals(
            [
                'id' => $id,
            ],
            $metadata
        );
    }

    public function test_it_builds_nothing_for_guests(): void
    {
        // Arrange

        $action = resolve(BuildCurrentUserAction::class);

        // Act

        $metadata = $action->execute();

        // Assert

        $this->assertEmpty($metadata);
    }
}
