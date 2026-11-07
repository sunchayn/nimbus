<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Intellisense;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Intellisense\InsightIntellisense;

#[CoversClass(InsightIntellisense::class)]
class InsightIntellisenseUnitTest extends TestCase
{
    public function test_it_targets_the_insight_file(): void
    {
        // Act & Assert

        $this->assertSame('insight.ts', (new InsightIntellisense)->getTargetFileName());
    }

    public function test_it_declares_one_interface_for_each_alias_and_one_for_the_contract(): void
    {
        // Arrange

        $intellisense = new InsightIntellisense;

        // Act

        $typescript = $intellisense->generate();

        // Assert

        foreach (['Query', 'Log', 'Gate', 'CacheEntry', 'JobEntry', 'MailEntry', 'NotificationEntry', 'HttpEntry', 'EventEntry', 'Frame', 'ExceptionInfo', 'Dropped'] as $alias) {
            $this->assertStringContainsString("export interface Insight{$alias} {", $typescript);
        }

        $this->assertStringContainsString('export interface Insight {', $typescript);
    }

    public function test_it_maps_the_members_of_an_alias(): void
    {
        // Act

        $typescript = (new InsightIntellisense)->generate();

        // Assert

        $this->assertStringContainsString(
            "export interface InsightQuery {\n    sql: string;\n    bindings: unknown[];\n    time_ms: number;\n    offset_ms: number;\n    connection: string;\n}",
            $typescript,
        );
    }

    public function test_it_maps_a_literal_string_union_and_a_nullable_number(): void
    {
        // Act

        $typescript = (new InsightIntellisense)->generate();

        // Assert

        $this->assertStringContainsString("    operation: 'hit' | 'miss' | 'write' | 'forget';", $typescript);
        $this->assertStringContainsString('    duration_ms: number | null;', $typescript);
    }

    public function test_it_uses_the_wire_keys_and_the_alias_names_in_the_contract(): void
    {
        // Act

        $typescript = (new InsightIntellisense)->generate();

        // Assert

        $this->assertStringContainsString('    queries: InsightQuery[];', $typescript);
        $this->assertStringContainsString('    gates: InsightGate[];', $typescript);
        $this->assertStringContainsString('    cache: InsightCacheEntry[];', $typescript);
        $this->assertStringContainsString('    http: InsightHttpEntry[];', $typescript);
        $this->assertStringNotContainsString('effects', $typescript);
        $this->assertStringContainsString('    exception: InsightExceptionInfo | null;', $typescript);
        $this->assertStringContainsString('    peak_memory: number;', $typescript);
        $this->assertStringContainsString('    dropped: InsightDropped;', $typescript);
        $this->assertStringContainsString('    truncated: boolean;', $typescript);
    }

    public function test_it_ends_with_one_new_line(): void
    {
        // Act

        $typescript = (new InsightIntellisense)->generate();

        // Assert

        $this->assertStringEndsWith("}\n", $typescript);
        $this->assertStringEndsNotWith("\n\n", $typescript);
    }
}
