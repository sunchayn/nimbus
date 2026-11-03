<?php

namespace Sunchayn\Nimbus\Tests\Integration\Workbench;

use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Tests\TestCase;
use Workbench\App\Http\Controllers\Demo\UploadsController;

#[CoversClass(UploadsController::class)]
class UploadsTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        /** @var Router $router */
        $router->prefix('demo')->group(__DIR__.'/../../../workbench/routes/demo.php');
    }

    public function test_it_accepts_a_single_multipart_file(): void
    {
        // Act

        $response = $this->postJson('demo/uploads/multipart/single', [
            'title' => 'Report',
            'file' => UploadedFile::fake()->createWithContent('report.txt', 'hello'),
        ]);

        // Assert

        $response->assertCreated();

        $response->assertJsonPath('title', 'Report');
        $response->assertJsonPath('file.name', 'report.txt');
        $response->assertJsonPath('file.size', 5);
        $response->assertJsonPath('file.sha256', hash('sha256', 'hello'));
    }

    public function test_it_accepts_multiple_multipart_files(): void
    {
        // Act

        $response = $this->postJson('demo/uploads/multipart/multiple', [
            'title' => 'Photos',
            'files' => [
                UploadedFile::fake()->createWithContent('a.txt', 'a'),
                UploadedFile::fake()->createWithContent('b.txt', 'bb'),
            ],
        ]);

        // Assert

        $response->assertCreated();

        $response->assertJsonCount(2, 'files');
        $response->assertJsonPath('files.0.name', 'a.txt');
        $response->assertJsonPath('files.1.size', 2);
    }

    public function test_it_accepts_mixed_multipart_fields(): void
    {
        // Act

        $response = $this->postJson('demo/uploads/multipart/mixed', [
            'title' => 'Mixed',
            'meta' => ['description' => 'Nested text'],
            'file' => UploadedFile::fake()->createWithContent('main.txt', 'main'),
            'attachments' => [UploadedFile::fake()->createWithContent('extra.txt', 'extra')],
        ]);

        // Assert

        $response->assertCreated();

        $response->assertJsonPath('description', 'Nested text');
        $response->assertJsonPath('file.name', 'main.txt');
        $response->assertJsonPath('attachments.0.name', 'extra.txt');
    }

    public function test_it_rejects_a_multipart_request_without_a_file(): void
    {
        $this->postJson('demo/uploads/multipart/single', ['title' => 'Report'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    public function test_it_accepts_a_single_base64_file(): void
    {
        // Act

        $response = $this->postJson('demo/uploads/json/single', [
            'title' => 'Report',
            'file' => [
                'name' => 'report.txt',
                'content' => base64_encode('hello'),
                'mime_type' => 'text/plain',
            ],
        ]);

        // Assert

        $response->assertCreated();

        $response->assertJsonPath('file.name', 'report.txt');
        $response->assertJsonPath('file.size', 5);
        $response->assertJsonPath('file.mime_type', 'text/plain');
        $response->assertJsonPath('file.sha256', hash('sha256', 'hello'));
    }

    public function test_it_accepts_multiple_base64_files_including_data_uris(): void
    {
        // Act

        $response = $this->postJson('demo/uploads/json/multiple', [
            'title' => 'Photos',
            'files' => [
                ['name' => 'a.txt', 'content' => base64_encode('a')],
                ['name' => 'b.png', 'content' => 'data:image/png;base64,'.base64_encode('bb')],
            ],
        ]);

        // Assert

        $response->assertCreated();

        $response->assertJsonPath('files.0.mime_type', 'application/octet-stream');
        $response->assertJsonPath('files.1.mime_type', 'image/png');
        $response->assertJsonPath('files.1.size', 2);
    }

    public function test_it_rejects_invalid_base64_content(): void
    {
        $this->postJson('demo/uploads/json/single', [
            'title' => 'Report',
            'file' => ['name' => 'report.txt', 'content' => '%%% not base64 %%%'],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file.content');
    }

    public function test_it_rejects_base64_content_over_the_size_limit(): void
    {
        $this->postJson('demo/uploads/json/multiple', [
            'title' => 'Report',
            'files' => [
                ['name' => 'big.bin', 'content' => base64_encode(str_repeat('a', 2 * 1024 * 1024 + 1))],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('files.0.content');
    }
}
