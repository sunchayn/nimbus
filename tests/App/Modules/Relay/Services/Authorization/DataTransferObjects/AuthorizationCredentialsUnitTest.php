<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Relay\Services\Authorization\DataTransferObjects;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\DataTransferObjects\AuthorizationCredentials;
use Sunchayn\Nimbus\Modules\Relay\Services\Authorization\Enums\AuthorizationTypeEnum;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(AuthorizationCredentials::class)]
class AuthorizationCredentialsUnitTest extends TestCase
{
    public function test_it_constructs_none_state(): void
    {
        $credentials = AuthorizationCredentials::none();

        // Assert

        $this->assertEquals(AuthorizationTypeEnum::None, $credentials->type);
        $this->assertNull($credentials->value);
    }
}
