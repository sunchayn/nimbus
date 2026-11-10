<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Toolbox\Actions;

use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions\HashValueAction;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(HashValueAction::class)]
class HashValueActionFunctionalTest extends TestCase
{
    public function test_it_makes_a_hash_when_none_is_given(): void
    {
        // Arrange

        $action = resolve(HashValueAction::class);

        // Act

        $result = $action->execute('password');

        // Assert

        $this->assertTrue(Hash::check('password', $result['hash']));
    }

    public function test_it_makes_a_hash_when_the_given_one_is_empty(): void
    {
        // Arrange

        $action = resolve(HashValueAction::class);

        // Act

        $result = $action->execute('password', '');

        // Assert

        $this->assertArrayHasKey('hash', $result);
    }

    public function test_it_reports_a_matching_hash(): void
    {
        // Arrange

        $action = resolve(HashValueAction::class);

        // Act

        $result = $action->execute('password', Hash::make('password'));

        // Assert

        $this->assertSame(['matches' => true, 'needs_rehash' => false], $result);
    }

    public function test_it_reports_a_hash_that_does_not_match(): void
    {
        // Arrange

        $action = resolve(HashValueAction::class);

        // Act

        $result = $action->execute('other', Hash::make('password'));

        // Assert

        $this->assertSame(['matches' => false, 'needs_rehash' => false], $result);
    }

    public function test_it_reports_a_matching_hash_that_needs_a_rehash(): void
    {
        // Arrange

        config()->set('hashing.driver', 'bcrypt');
        config()->set('hashing.bcrypt.rounds', 12);

        $weakHash = password_hash('password', PASSWORD_BCRYPT, ['cost' => 4]);

        $action = resolve(HashValueAction::class);

        // Act

        $result = $action->execute('password', $weakHash);

        // Assert

        $this->assertSame(['matches' => true, 'needs_rehash' => true], $result);
    }
}
