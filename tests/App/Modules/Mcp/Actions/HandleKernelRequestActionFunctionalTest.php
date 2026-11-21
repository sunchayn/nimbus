<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Mcp\Actions\HandleKernelRequestAction;
use Sunchayn\Nimbus\Modules\Mcp\DataTransferObjects\ExecutionRequestData;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(HandleKernelRequestAction::class)]
class HandleKernelRequestActionFunctionalTest extends TestCase
{
    public function test_it_handles_a_request_through_the_kernel(): void
    {
        // Arrange

        Route::post('_mcp/echo', fn (Request $request) => response()->json([
            'body' => $request->json()->all(),
            'query' => $request->query('page'),
            'transaction' => $request->header('X-Nimbus-Transaction-Mode'),
            'custom' => $request->header('X-Custom'),
        ], 201));

        $executionRequestData = new ExecutionRequestData(
            method: 'POST',
            path: '/_mcp/echo',
            query: ['page' => '2'],
            headers: ['X-Custom' => 'yes'],
            body: ['name' => 'Ada'],
            transactionMode: true,
        );

        $action = resolve(HandleKernelRequestAction::class);

        // Act

        $result = $action->execute($executionRequestData);

        // Assert

        $this->assertSame(
            201,
            $result->status,
        );

        $this->assertSame(
            [
                'body' => ['name' => 'Ada'],
                'query' => '2',
                'transaction' => '1',
                'custom' => 'yes',
            ],
            json_decode($result->body, true),
        );

        $this->assertSame(
            'application/json',
            $result->headers['content-type'],
        );

        $this->assertNotNull($result->insight);
    }

    public function test_it_sends_the_body_of_a_read_request_as_query_values(): void
    {
        // Arrange

        Route::get('_mcp/search', fn (Request $request) => response()->json($request->query()));

        $executionRequestData = new ExecutionRequestData(
            method: 'GET',
            path: '/_mcp/search',
            body: ['q' => 'x'],
        );

        $action = resolve(HandleKernelRequestAction::class);

        // Act

        $result = $action->execute($executionRequestData);

        // Assert

        $this->assertSame(
            ['q' => 'x'],
            json_decode($result->body, true),
        );
    }

    public function test_it_handles_a_request_without_a_query_string(): void
    {
        // Arrange

        Route::get('_mcp/plain', fn (Request $request) => response()->json(['uri' => $request->getRequestUri()]));

        $executionRequestData = new ExecutionRequestData(
            method: 'GET',
            path: '/_mcp/plain',
        );

        $action = resolve(HandleKernelRequestAction::class);

        // Act

        $result = $action->execute($executionRequestData);

        // Assert

        $this->assertSame(
            ['uri' => '/_mcp/plain'],
            json_decode($result->body, true),
        );
    }

    public function test_it_sends_a_string_body_as_it_is(): void
    {
        // Arrange

        Route::post('_mcp/raw', fn (Request $request) => response($request->getContent()));

        $executionRequestData = new ExecutionRequestData(
            method: 'POST',
            path: '/_mcp/raw',
            body: 'plain text',
        );

        $action = resolve(HandleKernelRequestAction::class);

        // Act

        $result = $action->execute($executionRequestData);

        // Assert

        $this->assertSame(
            'plain text',
            $result->body,
        );
    }
}
