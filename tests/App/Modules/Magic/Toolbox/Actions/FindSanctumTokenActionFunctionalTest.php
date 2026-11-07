<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Toolbox\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions\FindSanctumTokenAction;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Exceptions\InvalidToolInputException;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(FindSanctumTokenAction::class)]
class FindSanctumTokenActionFunctionalTest extends TestCase
{
    public function test_it_breaks_when_sanctum_is_not_installed(): void
    {
        // Arrange

        if (class_exists('Laravel\\Sanctum\\PersonalAccessToken')) {
            $this->markTestSkipped('Sanctum is installed.');
        }

        $action = resolve(FindSanctumTokenAction::class);

        // Anticipate

        $this->expectException(InvalidToolInputException::class);
        $this->expectExceptionMessage('Laravel Sanctum is not installed.');

        // Act

        $action->execute('1|abc');
    }
}
