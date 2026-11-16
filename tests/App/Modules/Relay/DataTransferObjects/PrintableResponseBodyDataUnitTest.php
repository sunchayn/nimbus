<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Relay\DataTransferObjects;

use Illuminate\Http\Client\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Relay\DataTransferObjects\PrintableResponseBodyData;

#[CoversClass(PrintableResponseBodyData::class)]
class PrintableResponseBodyDataUnitTest extends TestCase
{
    public function test_it_constructs_with_array_body(): void
    {
        // Arrange

        $body = ['foo' => 'bar', 'bar' => 'http://example.com/foo'];

        // Act

        $printable = new PrintableResponseBodyData($body);

        // Assert

        $this->assertSame($body, $printable->body);

        $this->assertSame(<<<'EXPECTED'
{
    "foo": "bar",
    "bar": "http://example.com/foo"
}
EXPECTED,
            $printable->toPrettyJSON(),
        );
    }

    public function test_it_constructs_with_string_body(): void
    {
        // Arrange

        $body = 'plain string';

        // Act

        $printable = new PrintableResponseBodyData($body);

        // Assert

        $this->assertSame($body, $printable->body);

        $this->assertSame($body, $printable->toPrettyJSON());
    }

    public function test_it_creates_from_response_with_json_body(): void
    {
        // Arrange

        $jsonData = ['hello' => 'world'];

        $response = $this->mockResponse(jsonMethodReturn: $jsonData);

        // Act

        $printable = PrintableResponseBodyData::fromResponse($response);

        // Assert

        $this->assertSame($jsonData, $printable->body);

        $this->assertSame(json_encode($jsonData, JSON_PRETTY_PRINT), $printable->toPrettyJSON());
    }

    public function test_it_from_response_with_string_body(): void
    {
        // Arrange

        $stringData = 'raw string body';

        // Act

        $response = $this->mockResponse(bodyMethodReturn: $stringData);

        // Assert

        $printable = PrintableResponseBodyData::fromResponse($response);

        $this->assertSame($stringData, $printable->body);
        $this->assertSame($stringData, $printable->toPrettyJSON());
    }

    /*
     * Mocks.
     */

    private function mockResponse(?array $jsonMethodReturn = null, ?string $bodyMethodReturn = null): Response
    {
        $mock = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock->method('json')->willReturn($jsonMethodReturn);
        $mock->method('body')->willReturn($bodyMethodReturn ?? '');

        return $mock;
    }
}
