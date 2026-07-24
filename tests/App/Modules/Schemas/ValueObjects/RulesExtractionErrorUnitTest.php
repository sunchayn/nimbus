<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Schemas\ValueObjects;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\RulesExtractionError;

#[CoversClass(RulesExtractionError::class)]
class RulesExtractionErrorUnitTest extends TestCase
{
    public function test_it_formats_exception_message_and_trace_to_html(): void
    {
        // Arrange

        $exception = new Exception('Validation failed during AST parsing');
        $error = new RulesExtractionError($exception);

        // Act

        $html = $error->toHtml();

        // Assert

        $this->assertStringContainsString('<b>Validation failed during AST parsing</b>', $html);
        $this->assertStringContainsString('<small>'.__FILE__, $html);
        $this->assertStringContainsString('<p class="text-xs">', $html);
    }

    public function test_it_uses_fallback_text_when_exception_message_is_empty(): void
    {
        // Arrange

        $exception = new Exception('');
        $error = new RulesExtractionError($exception);

        // Act

        $html = $error->toHtml();

        // Assert

        $this->assertStringContainsString('<b>[no error message]</b>', $html);
    }
}
