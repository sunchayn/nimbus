<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Workspace\DataTransferObjects;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\RequestFile;
use Sunchayn\Nimbus\Modules\Workspace\DataTransferObjects\SavedRequest;
use Sunchayn\Nimbus\Modules\Workspace\ValueObjects\FolderPath;

#[CoversClass(RequestFile::class)]
class RequestFileUnitTest extends TestCase
{
    public function test_it_gives_the_request_and_the_hash_as_the_api_returns_them(): void
    {
        // Arrange

        $savedRequest = SavedRequest::fromArray([
            'id' => 'request-1',
            'name' => 'Create user',
            'method' => 'post',
            'endpoint' => '/api/users',
        ]);

        $requestFile = new RequestFile(
            filepath: '/workspace/requests/users/create-user.request.json',
            relativeFilepath: 'requests/users/create-user.request.json',
            folder: FolderPath::fromStored('users'),
            savedRequest: $savedRequest,
            hash: 'file-hash',
        );

        // Act

        $payload = $requestFile->toArray();

        // Assert

        $this->assertSame(
            ['request', 'hash'],
            array_keys($payload),
        );

        $this->assertSame(
            $savedRequest->toArray(),
            $payload['request'],
        );

        $this->assertSame(
            'file-hash',
            $payload['hash'],
        );
    }
}
