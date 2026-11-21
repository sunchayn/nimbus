<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Mcp\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Sunchayn\Nimbus\Modules\Mcp\Actions\ExplainResponseAction;
use Sunchayn\Nimbus\Modules\Mcp\Exceptions\McpToolException;
use Sunchayn\Nimbus\Modules\Mcp\Services\SentRequestStore;
use Sunchayn\Nimbus\Tests\Concerns\UsesTemporaryBasePath;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(ExplainResponseAction::class)]
class ExplainResponseActionFunctionalTest extends TestCase
{
    use UsesTemporaryBasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useTemporaryBasePath();
    }

    public function test_it_explains_an_exception_first(): void
    {
        // Arrange

        $exception = [
            'class' => 'LogicException',
            'message' => 'Boom',
            'file' => 'a.php',
            'line' => 9,
            'trace' => ['b.php:3 call()'],
        ];

        $id = $this->storeSentRequest(
            status: 500,
            body: 'x',
            insight: $this->insight(['exception' => $exception]),
        );

        $action = resolve(ExplainResponseAction::class);

        // Act

        $result = $action->execute($id);

        // Assert

        $this->assertStringContainsString(
            'LogicException',
            $result['likelyCause'],
        );

        $this->assertContains(
            'Thrown at a.php:9.',
            $result['evidence'],
        );
    }

    public function test_it_names_the_fields_of_a_validation_error(): void
    {
        // Arrange

        $id = $this->storeSentRequest(
            status: 422,
            body: ['errors' => ['email' => ['The email field is required.']]],
            insight: $this->insight(),
        );

        $action = resolve(ExplainResponseAction::class);

        // Act

        $result = $action->execute($id);

        // Assert

        $this->assertSame(
            'Validation failed on `email`.',
            $result['likelyCause'],
        );

        $this->assertContains(
            '`email`: The email field is required.',
            $result['evidence'],
        );
    }

    public function test_it_reports_the_denied_authorization_check(): void
    {
        // Arrange

        $authCheck = ['ability' => 'update', 'result' => false, 'arguments' => [], 'user' => '5'];

        $id = $this->storeSentRequest(
            status: 403,
            body: [],
            insight: $this->insight(['authChecks' => [$authCheck]]),
        );

        $action = resolve(ExplainResponseAction::class);

        // Act

        $result = $action->execute($id);

        // Assert

        $this->assertContains(
            'Denied `update` for user 5.',
            $result['evidence'],
        );
    }

    public function test_it_works_without_an_insight_and_for_a_success(): void
    {
        // Arrange

        $id = $this->storeSentRequest(
            status: 200,
            body: [],
        );

        $action = resolve(ExplainResponseAction::class);

        // Act

        $result = $action->execute($id);

        // Assert

        $this->assertSame(
            'The request was handled without an error.',
            $result['likelyCause'],
        );

        $this->assertStringContainsString(
            'No insight',
            $result['evidence'][0],
        );
    }

    /**
     * @param  array<string, mixed>  $body
     */
    #[DataProvider('statusProvider')]
    public function test_it_explains_a_status(int $status, mixed $body, string $expectedCause): void
    {
        // Arrange

        $id = $this->storeSentRequest(
            status: $status,
            body: $body,
            insight: $this->insight(),
        );

        $action = resolve(ExplainResponseAction::class);

        // Act

        $result = $action->execute($id);

        // Assert

        $this->assertStringContainsString(
            $expectedCause,
            $result['likelyCause'],
        );
    }

    /**
     * @return array<string, array{int, mixed, string}>
     */
    public static function statusProvider(): array
    {
        return [
            'unauthenticated' => [401, [], 'not authenticated'],
            'forbidden without a recorded check' => [403, [], 'not recorded'],
            'not found without a message' => [404, [], 'No route matched, or a bound model was not found.'],
            'not found with a message' => [404, ['message' => 'No query results'], 'Message: No query results'],
            'method not allowed' => [405, [], 'does not accept this method'],
            'csrf mismatch' => [419, [], 'CSRF'],
            'validation without errors' => [422, [], 'Validation failed.'],
            'throttled' => [429, [], 'throttled'],
            'server error without an exception' => [500, [], 'no exception was recorded'],
        ];
    }

    public function test_it_lists_the_logs_and_the_slowest_query(): void
    {
        // Arrange

        $id = $this->storeSentRequest(
            status: 200,
            body: [],
            insight: $this->insight([
                'queryCount' => 2,
                'queries' => [
                    ['sql' => 'select 1', 'timeMs' => 1.5],
                    ['sql' => 'select 2', 'timeMs' => 9.5],
                ],
                'logs' => [
                    ['level' => 'warning', 'message' => 'Careful'],
                ],
            ]),
        );

        $action = resolve(ExplainResponseAction::class);

        // Act

        $result = $action->execute($id);

        // Assert

        $this->assertContains('Log warning: Careful', $result['evidence']);

        $this->assertContains('2 queries ran.', $result['evidence']);

        $this->assertContains('Slowest query, 9.5 ms: select 2', $result['evidence']);
    }

    public function test_it_reports_a_guest_in_a_denied_check(): void
    {
        // Arrange

        $authCheck = ['ability' => 'view', 'result' => false, 'arguments' => [], 'user' => null];

        $id = $this->storeSentRequest(
            status: 403,
            body: [],
            insight: $this->insight(['authChecks' => [$authCheck]]),
        );

        $action = resolve(ExplainResponseAction::class);

        // Act

        $result = $action->execute($id);

        // Assert

        $this->assertContains(
            'Denied `view` for a guest.',
            $result['evidence'],
        );
    }

    public function test_it_fails_for_an_unknown_sent_request_id(): void
    {
        // Arrange

        $action = resolve(ExplainResponseAction::class);

        // Assert

        $this->expectException(McpToolException::class);

        // Act

        $action->execute('nope');
    }

    /*
     * Generators.
     */

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function insight(array $overrides = []): array
    {
        return $overrides + [
            'exception' => null,
            'authChecks' => [],
            'queryCount' => 0,
            'queries' => [],
            'logs' => [],
        ];
    }

    /*
     * Seeders.
     */

    /**
     * @param  array<string, mixed>|null  $insight
     */
    private function storeSentRequest(int $status, mixed $body, ?array $insight = null): string
    {
        return resolve(SentRequestStore::class)->put([
            'route' => 'POST api/users',
            'response' => ['status' => $status, 'headers' => [], 'body' => $body],
            'insight' => $insight,
        ]);
    }
}
