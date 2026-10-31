<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Ast\Actions;

use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;
use Sunchayn\Nimbus\Modules\Ast\Actions\ResolveClassFileAction;
use Sunchayn\Nimbus\Modules\Ast\Events\ClassFileResolved;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ResolveClassFileAction::class)]
class ResolveClassFileActionFunctionalTest extends TestCase
{
    public function test_it_returns_the_class_file_and_announces_it(): void
    {
        // Arrange

        Event::fake([ClassFileResolved::class]);

        $action = resolve(ResolveClassFileAction::class);

        // Act

        $fileName = $action->execute(self::class);

        // Assert

        $this->assertSame(__FILE__, $fileName);

        Event::assertDispatched(
            ClassFileResolved::class,
            fn (ClassFileResolved $event): bool => $event->path === __FILE__,
        );
    }

    public function test_it_announces_nothing_for_a_class_without_a_file(): void
    {
        // Arrange

        Event::fake([ClassFileResolved::class]);

        $action = resolve(ResolveClassFileAction::class);

        // Act

        $fileName = $action->execute(stdClass::class);

        // Assert

        $this->assertNull($fileName);

        Event::assertNotDispatched(ClassFileResolved::class);
    }
}
