<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Magic\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Magic\Actions\PullInsightAction;
use Sunchayn\Nimbus\Modules\Magic\Services\Insights\InsightStore;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(PullInsightAction::class)]
class PullInsightActionFunctionalTest extends TestCase
{
    private const ID = '12345678-aaaa-bbbb-cccc-1234567890ab';

    public function test_it_rebuilds_the_insight_the_store_returns(): void
    {
        // Arrange

        $insightStoreSpy = $this->spy(InsightStore::class);
        $insightStoreSpy->shouldReceive('pull')->andReturn([
            'logs' => [['level' => 'info', 'message' => 'a', 'context' => []]],
            'dropped' => ['queries' => 0, 'logs' => 4, 'cache' => 0, 'jobs' => 0, 'mail' => 0, 'notifications' => 0, 'http' => 0, 'events' => 0],
            'peak_memory' => 10,
        ]);

        $action = resolve(PullInsightAction::class);

        // Act

        $insight = $action->execute(self::ID);

        // Assert

        $this->assertSame('a', $insight->logs->entries[0]['message']);
        $this->assertSame(4, $insight->logs->dropped);
        $this->assertSame(10, $insight->peakMemory);

        $insightStoreSpy
            ->shouldHaveReceived('pull')
            ->once()
            ->withArgs(function (string $id): bool {
                $this->assertSame(self::ID, $id);

                return true;
            });
    }

    public function test_it_returns_null_when_the_store_has_nothing(): void
    {
        // Arrange

        $insightStoreSpy = $this->spy(InsightStore::class);
        $insightStoreSpy->shouldReceive('pull')->andReturn(null);

        $action = resolve(PullInsightAction::class);

        // Act

        $insight = $action->execute(self::ID);

        // Assert

        $this->assertNull($insight);
    }

    public function test_it_reads_a_file_the_store_wrote(): void
    {
        // Arrange

        resolve(InsightStore::class)->write(self::ID, '{"peak_memory":5,"truncated":true}');

        $action = resolve(PullInsightAction::class);

        // Act

        $insight = $action->execute(self::ID);

        // Assert

        $this->assertSame(5, $insight->peakMemory);
        $this->assertTrue($insight->truncated);
        $this->assertNull(resolve(InsightStore::class)->pull(self::ID));
    }

    public function test_it_creates_ids_the_store_accepts(): void
    {
        // Act

        $first = PullInsightAction::newId();
        $second = PullInsightAction::newId();

        // Assert

        $this->assertTrue(InsightStore::isValidId($first));
        $this->assertNotSame($first, $second);
    }
}
