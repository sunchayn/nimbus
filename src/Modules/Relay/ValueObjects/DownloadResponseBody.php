<?php

namespace Sunchayn\Nimbus\Modules\Relay\ValueObjects;

use Illuminate\Http\Client\Response;

readonly class DownloadResponseBody
{
    public const DEFAULT_FILENAME = 'download';

    public const DEFAULT_MIME_TYPE = 'application/octet-stream';

    public function __construct(
        public string $filename,
        public string $mimeType,
        public int $sizeInBytes,
        public string $content,
    ) {}

    /**
     * A response is a download when it is sent as an attachment or its body is not valid text.
     */
    public static function tryFromResponse(Response $response, string $endpoint): ?self
    {
        $contentDisposition = $response->header('Content-Disposition');

        $body = $response->body();

        $isAttachment = str_starts_with(strtolower(trim($contentDisposition)), 'attachment');

        if (! $isAttachment && mb_check_encoding($body, 'UTF-8')) {
            return null;
        }

        return new self(
            filename: self::resolveFilename($contentDisposition, $endpoint),
            mimeType: self::resolveMimeType($response),
            sizeInBytes: strlen($body),
            content: base64_encode($body),
        );
    }

    /**
     * @return array{filename: string, mimeType: string, sizeInBytes: int, content: string}
     */
    public function toArray(): array
    {
        return [
            'filename' => $this->filename,
            'mimeType' => $this->mimeType,
            'sizeInBytes' => $this->sizeInBytes,
            'content' => $this->content,
        ];
    }

    private static function resolveMimeType(Response $response): string
    {
        $contentType = trim(explode(';', $response->header('Content-Type'))[0]);

        return $contentType !== '' ? $contentType : self::DEFAULT_MIME_TYPE;
    }

    private static function resolveFilename(string $contentDisposition, string $endpoint): string
    {
        if (preg_match("/filename\\*\\s*=\\s*[^']*'[^']*'([^;]+)/i", $contentDisposition, $matches) === 1) {
            return rawurldecode(trim($matches[1], " \t\"'"));
        }

        if (preg_match('/filename\s*=\s*("([^"]*)"|[^;]+)/i', $contentDisposition, $matches) === 1) {
            return trim($matches[2] ?? $matches[1], " \t\"'");
        }

        $lastSegment = basename((string) parse_url($endpoint, PHP_URL_PATH));

        return $lastSegment !== '' ? rawurldecode($lastSegment) : self::DEFAULT_FILENAME;
    }
}
