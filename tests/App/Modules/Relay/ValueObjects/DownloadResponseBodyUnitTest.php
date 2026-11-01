<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Relay\ValueObjects;

use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Relay\ValueObjects\DownloadResponseBody;

#[CoversClass(DownloadResponseBody::class)]
class DownloadResponseBodyUnitTest extends TestCase
{
    private const ENDPOINT = 'https://localhost/api/reports/export.csv?year=2025';

    #[TestWith(['attachment; filename="report.csv"', 'report.csv'])]
    #[TestWith(['attachment; filename=report.csv', 'report.csv'])]
    #[TestWith(["attachment; filename*=UTF-8''my%20report.csv", 'my report.csv'])]
    #[TestWith(['attachment; filename="fallback.csv"; filename*=UTF-8\'\'real.csv', 'real.csv'])]
    #[TestWith(['attachment', 'export.csv'])]
    public function test_it_detects_attachment_and_resolves_filename(string $header, string $expectedFilename): void
    {
        // Arrange

        $response = $this->makeResponse('a,b', ['Content-Disposition' => $header, 'Content-Type' => 'text/csv; charset=UTF-8']);

        // Act

        $download = DownloadResponseBody::tryFromResponse($response, self::ENDPOINT);

        // Assert

        $this->assertNotNull($download);
        $this->assertSame($expectedFilename, $download->filename);
        $this->assertSame('text/csv', $download->mimeType);
        $this->assertSame(3, $download->sizeInBytes);
        $this->assertSame(base64_encode('a,b'), $download->content);
    }

    public function test_it_detects_binary_body_without_attachment_header(): void
    {
        // Arrange

        $binary = "\x89PNG\r\n\x1a\n\xff\xfe";

        $response = $this->makeResponse($binary, ['Content-Type' => 'image/png']);

        // Act

        $download = DownloadResponseBody::tryFromResponse($response, 'https://localhost/');

        // Assert

        $this->assertNotNull($download);
        $this->assertSame('download', $download->filename);
        $this->assertSame('image/png', $download->mimeType);
        $this->assertSame(strlen($binary), $download->sizeInBytes);
        $this->assertSame($binary, base64_decode($download->content, true));
    }

    public function test_it_falls_back_to_octet_stream_without_content_type(): void
    {
        // Arrange

        $response = $this->makeResponse("\xff\xfe", []);

        // Act

        $download = DownloadResponseBody::tryFromResponse($response, self::ENDPOINT);

        // Assert

        $this->assertSame('application/octet-stream', $download?->mimeType);
    }

    public function test_it_returns_null_for_regular_text_responses(): void
    {
        // Arrange

        $response = $this->makeResponse('{"ok":true}', ['Content-Type' => 'application/json']);

        // Act & Assert

        $this->assertNull(DownloadResponseBody::tryFromResponse($response, self::ENDPOINT));
    }

    public function test_it_converts_to_array(): void
    {
        // Arrange

        $download = new DownloadResponseBody('a.txt', 'text/plain', 1, 'YQ==');

        // Act & Assert

        $this->assertSame([
            'filename' => 'a.txt',
            'mimeType' => 'text/plain',
            'sizeInBytes' => 1,
            'content' => 'YQ==',
        ], $download->toArray());
    }

    /*
     * Helpers.
     */

    /**
     * @param  array<string, string>  $headers
     */
    private function makeResponse(string $body, array $headers): Response
    {
        return new Response(new PsrResponse(200, $headers, $body));
    }
}
