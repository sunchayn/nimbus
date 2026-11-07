<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Toolbox\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions\DecodeJwtAction;
use Sunchayn\Nimbus\Modules\Magic\Toolbox\Exceptions\InvalidToolInputException;

#[CoversClass(DecodeJwtAction::class)]
class DecodeJwtActionUnitTest extends TestCase
{
    public function test_it_decodes_the_header_and_the_payload(): void
    {
        // Arrange

        $token = $this->encode(['alg' => 'HS256']).'.'.$this->encode(['sub' => '1']).'.signature';

        $action = new DecodeJwtAction;

        // Act

        $result = $action->execute($token);

        // Assert

        $this->assertSame(['header' => ['alg' => 'HS256'], 'payload' => ['sub' => '1']], $result);
    }

    public function test_it_ignores_the_signature(): void
    {
        // Arrange

        $token = $this->encode(['alg' => 'none']).'.'.$this->encode(['sub' => '2']).'.not-a-real-signature';

        $action = new DecodeJwtAction;

        // Act

        $result = $action->execute('  '.$token.'  ');

        // Assert

        $this->assertSame('2', $result['payload']['sub']);
    }

    public function test_it_breaks_when_the_token_has_the_wrong_number_of_parts(): void
    {
        // Arrange

        $action = new DecodeJwtAction;

        // Anticipate

        $this->expectException(InvalidToolInputException::class);
        $this->expectExceptionMessage('The value is not a valid JWT.');

        // Act

        $action->execute('only.two');
    }

    public function test_it_breaks_when_a_part_is_not_json(): void
    {
        // Arrange

        $action = new DecodeJwtAction;

        // Anticipate

        $this->expectException(InvalidToolInputException::class);

        // Act

        $action->execute('a.b.c');
    }

    /**
     * @param  array<string, mixed>  $part
     */
    private function encode(array $part): string
    {
        return rtrim(strtr(base64_encode((string) json_encode($part)), '+/', '-_'), '=');
    }
}
