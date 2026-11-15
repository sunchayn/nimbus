<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Extractor\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Extractor\Actions\ReadFormRequestHooksAction;
use Sunchayn\Nimbus\Tests\TestCase;
use Workbench\App\Http\Requests\Demo\JsonUploadSingleRequest;
use Workbench\App\Http\Requests\Demo\RuleObjectsRequest;

#[CoversClass(ReadFormRequestHooksAction::class)]
class ReadFormRequestHooksActionFunctionalTest extends TestCase
{
    public function test_it_reads_the_hooks_of_a_form_request(): void
    {
        // Arrange

        $action = resolve(ReadFormRequestHooksAction::class);

        // Act

        $hooks = $action->execute(RuleObjectsRequest::class);

        // Assert

        $this->assertSame(['slug'], $hooks->mergedKeys);
        $this->assertSame(['title' => 'Post title'], $hooks->labels);
        $this->assertTrue($hooks->authorizeReturnsFalse);
    }

    public function test_it_returns_empty_hooks_for_a_form_request_without_them(): void
    {
        // Arrange

        $action = resolve(ReadFormRequestHooksAction::class);

        // Act

        $hooks = $action->execute(JsonUploadSingleRequest::class);

        // Assert

        $this->assertSame([], $hooks->mergedKeys);
        $this->assertSame([], $hooks->labels);
        $this->assertFalse($hooks->authorizeReturnsFalse);
    }
}
