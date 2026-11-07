<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration\Toolbox;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Http\Api\Toolbox\ToolboxController;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions\DecodeJwtAction;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions\FindSanctumTokenAction;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Exceptions\InvalidToolInputException;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ToolboxController::class)]
class TokenTest extends TestCase
{
    public function test_it_requires_a_known_type_and_a_token(): void
    {
        // Act

        $response = $this->postJson(route('nimbus.api.toolbox.token'), ['type' => 'other']);

        // Assert

        $response->assertUnprocessable()->assertJsonValidationErrors(['type', 'token']);
    }

    public function test_it_decodes_a_jwt_with_the_jwt_action(): void
    {
        // Arrange

        $jwtSpy = $this->spy(DecodeJwtAction::class);
        $sanctumSpy = $this->spy(FindSanctumTokenAction::class);

        // Anticipate

        $jwtSpy->shouldReceive('execute')->andReturn(['header' => ['alg' => 'HS256'], 'payload' => ['sub' => '1']]);

        // Act

        $response = $this->postJson(route('nimbus.api.toolbox.token'), ['type' => 'jwt', 'token' => '::jwt::']);

        // Assert

        $response->assertOk()->assertExactJson(['header' => ['alg' => 'HS256'], 'payload' => ['sub' => '1']]);

        $jwtSpy
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (string $token): bool {
                $this->assertSame('::jwt::', $token);

                return true;
            });

        $sanctumSpy->shouldNotHaveReceived('execute');
    }

    public function test_it_finds_a_sanctum_token_with_the_sanctum_action(): void
    {
        // Arrange

        $jwtSpy = $this->spy(DecodeJwtAction::class);
        $sanctumSpy = $this->spy(FindSanctumTokenAction::class);

        // Anticipate

        $sanctumSpy->shouldReceive('execute')->andReturn(['id' => 1, 'name' => 'cli']);

        // Act

        $response = $this->postJson(route('nimbus.api.toolbox.token'), ['type' => 'sanctum', 'token' => '1|abc']);

        // Assert

        $response->assertOk()->assertExactJson(['id' => 1, 'name' => 'cli']);

        $sanctumSpy
            ->shouldHaveReceived('execute')
            ->once()
            ->withArgs(function (string $token): bool {
                $this->assertSame('1|abc', $token);

                return true;
            });

        $jwtSpy->shouldNotHaveReceived('execute');
    }

    public function test_it_reports_a_token_that_cannot_be_read(): void
    {
        // Arrange

        $jwtSpy = $this->spy(DecodeJwtAction::class);

        // Anticipate

        $jwtSpy->shouldReceive('execute')->andThrow(InvalidToolInputException::becauseTokenIsNotAJwt());

        // Act

        $response = $this->postJson(route('nimbus.api.toolbox.token'), ['type' => 'jwt', 'token' => 'a.b.c']);

        // Assert

        $response->assertUnprocessable()->assertExactJson(['message' => 'The value is not a valid JWT.']);
    }

    public function test_it_integrates(): void
    {
        // Arrange

        $encode = fn (array $part): string => rtrim(strtr(base64_encode((string) json_encode($part)), '+/', '-_'), '=');

        $token = $encode(['alg' => 'HS256']).'.'.$encode(['sub' => '1']).'.signature';

        // Act

        $response = $this->postJson(route('nimbus.api.toolbox.token'), ['type' => 'jwt', 'token' => $token]);

        // Assert

        $response->assertOk()->assertExactJson(['header' => ['alg' => 'HS256'], 'payload' => ['sub' => '1']]);
    }
}
