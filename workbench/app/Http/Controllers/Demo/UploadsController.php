<?php

namespace Workbench\App\Http\Controllers\Demo;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Workbench\App\Http\Requests\Demo\JsonUploadMultipleRequest;
use Workbench\App\Http\Requests\Demo\JsonUploadSingleRequest;
use Workbench\App\Http\Requests\Demo\MultipartUploadMixedRequest;
use Workbench\App\Http\Requests\Demo\MultipartUploadMultipleRequest;
use Workbench\App\Http\Requests\Demo\MultipartUploadSingleRequest;

/**
 * Accepts files as multipart parts or as base64 strings inside JSON, and returns a summary of each.
 */
class UploadsController
{
    private const MAX_DECODED_BYTES = 2 * 1024 * 1024;

    public function multipartSingle(MultipartUploadSingleRequest $request): JsonResponse
    {
        return response()->json([
            'title' => $request->validated('title'),
            'file' => $this->summarizeUploadedFile($request->file('file')),
        ], 201);
    }

    public function multipartMultiple(MultipartUploadMultipleRequest $request): JsonResponse
    {
        return response()->json([
            'title' => $request->validated('title'),
            'files' => array_map($this->summarizeUploadedFile(...), $request->file('files')),
        ], 201);
    }

    public function multipartMixed(MultipartUploadMixedRequest $request): JsonResponse
    {
        return response()->json([
            'title' => $request->validated('title'),
            'description' => $request->validated('meta.description'),
            'file' => $this->summarizeUploadedFile($request->file('file')),
            'attachments' => array_map($this->summarizeUploadedFile(...), $request->file('attachments', [])),
        ], 201);
    }

    public function jsonSingle(JsonUploadSingleRequest $request): JsonResponse
    {
        return response()->json([
            'title' => $request->validated('title'),
            'file' => $this->summarizeEncodedFile($request->validated('file'), 'file'),
        ], 201);
    }

    public function jsonMultiple(JsonUploadMultipleRequest $request): JsonResponse
    {
        return response()->json([
            'title' => $request->validated('title'),
            'files' => collect($request->validated('files'))
                ->map(fn (array $file, int $index): array => $this->summarizeEncodedFile($file, "files.$index"))
                ->all(),
        ], 201);
    }

    /**
     * @return array{name: string, size: int, mime_type: string, sha256: string}
     */
    private function summarizeUploadedFile(UploadedFile $file): array
    {
        return [
            'name' => $file->getClientOriginalName(),
            'size' => (int) $file->getSize(),
            'mime_type' => (string) $file->getClientMimeType(),
            'sha256' => hash_file('sha256', $file->getRealPath()),
        ];
    }

    /**
     * @param  array{name: string, content: string, mime_type?: string|null}  $file
     * @return array{name: string, size: int, mime_type: string, sha256: string}
     */
    private function summarizeEncodedFile(array $file, string $field): array
    {
        $content = $file['content'];
        $mimeType = $file['mime_type'] ?? null;

        // A data URI carries the mime type before the base64 payload.
        if (preg_match('#^data:([\w.+-]+/[\w.+-]+)?;base64,#', $content, $matches) === 1) {
            $mimeType ??= $matches[1] ?: null;
            $content = substr($content, strlen($matches[0]));
        }

        $binary = base64_decode($content, true);

        if ($binary === false) {
            throw ValidationException::withMessages([$field.'.content' => 'The content must be valid base64.']);
        }

        if (strlen($binary) > self::MAX_DECODED_BYTES) {
            throw ValidationException::withMessages([$field.'.content' => 'The decoded file may not be larger than 2 MB.']);
        }

        return [
            'name' => $file['name'],
            'size' => strlen($binary),
            'mime_type' => $mimeType ?? 'application/octet-stream',
            'sha256' => hash('sha256', $binary),
        ];
    }
}
