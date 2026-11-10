<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Extractor\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Extractor\Actions\ReadRequestQueryKeysAction;
use Sunchayn\Nimbus\Tests\TestCase;
use Workbench\App\Http\Controllers\Demo\QueryParametersController;

#[CoversClass(ReadRequestQueryKeysAction::class)]
class ReadRequestQueryKeysActionFunctionalTest extends TestCase
{
    public function test_it_reads_literal_keys_from_request_calls(): void
    {
        // Act

        $keys = resolve(ReadRequestQueryKeysAction::class)->execute(QueryParametersController::class, 'index');

        // Assert

        $this->assertEqualsCanonicalizing(['search', 'per_page', 'featured', 'status', 'author'], $keys);
    }

    public function test_it_returns_nothing_for_an_unknown_method(): void
    {
        // Act

        $keys = resolve(ReadRequestQueryKeysAction::class)->execute(QueryParametersController::class, 'missing');

        // Assert

        $this->assertSame([], $keys);
    }
}
