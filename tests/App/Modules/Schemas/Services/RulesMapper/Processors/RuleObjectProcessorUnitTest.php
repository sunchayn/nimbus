<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Schemas\Services\RulesMapper\Processors;

use Illuminate\Validation\Rules\ProhibitedIf;
use Illuminate\Validation\Rules\RequiredIf;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Schemas\Services\RulesMapper\Processors\RuleObjectProcessor;

#[CoversClass(RuleObjectProcessor::class)]
class RuleObjectProcessorUnitTest extends TestCase
{
    public function test_it_marks_a_field_required_when_the_condition_is_true(): void
    {
        // Act

        $result = RuleObjectProcessor::requiredIf(new RequiredIf(true));

        // Assert

        $this->assertSame(['required' => true], $result);
    }

    public function test_it_adds_nothing_when_the_required_condition_is_false(): void
    {
        // Act

        $result = RuleObjectProcessor::requiredIf(new RequiredIf(false));

        // Assert

        $this->assertSame([], $result);
    }

    public function test_it_describes_a_required_closure_without_running_it(): void
    {
        // Arrange

        $ran = false;

        $rule = new RequiredIf(function () use (&$ran): bool {
            $ran = true;

            return true;
        });

        // Act

        $result = RuleObjectProcessor::requiredIf($rule);

        // Assert

        $this->assertFalse($ran);
        $this->assertCount(1, $result['hints']);
        $this->assertStringStartsWith('required if a closure passes, defined in RuleObjectProcessorUnitTest.php line ', $result['hints'][0]);
    }

    public function test_it_prohibits_and_skips_autofill_when_the_condition_is_true(): void
    {
        // Act

        $result = RuleObjectProcessor::prohibitedIf(new ProhibitedIf(true));

        // Assert

        $this->assertSame(['hints' => ['prohibited'], 'skipAutofill' => true], $result);
    }

    public function test_it_adds_nothing_when_the_prohibited_condition_is_false(): void
    {
        // Act

        $result = RuleObjectProcessor::prohibitedIf(new ProhibitedIf(false));

        // Assert

        $this->assertSame([], $result);
    }

    public function test_it_describes_a_prohibited_closure(): void
    {
        // Act

        $result = RuleObjectProcessor::prohibitedIf(new ProhibitedIf(fn (): bool => true));

        // Assert

        $this->assertStringStartsWith('prohibited if a closure passes, defined in ', $result['hints'][0]);
    }

    public function test_it_describes_a_closure_rule(): void
    {
        // Act

        $result = RuleObjectProcessor::closure(function (string $attribute, mixed $value, \Closure $fail): void {});

        // Assert

        $this->assertStringStartsWith('custom closure rule, defined in RuleObjectProcessorUnitTest.php line ', $result['hints'][0]);
    }

    public function test_it_describes_a_custom_rule_with_the_first_line_of_its_docblock(): void
    {
        // Act

        $result = RuleObjectProcessor::custom(new DocumentedRuleStub);

        // Assert

        $this->assertSame(['hints' => ['custom rule DocumentedRuleStub, Passes when the value is even.']], $result);
    }

    public function test_it_describes_a_custom_rule_without_a_docblock(): void
    {
        // Act

        $result = RuleObjectProcessor::custom(new UndocumentedRuleStub);

        // Assert

        $this->assertSame(['hints' => ['custom rule UndocumentedRuleStub']], $result);
    }

    public function test_it_ignores_a_docblock_that_has_only_tags(): void
    {
        // Act

        $result = RuleObjectProcessor::custom(new TagOnlyRuleStub);

        // Assert

        $this->assertSame(['hints' => ['custom rule TagOnlyRuleStub']], $result);
    }
}

/**
 * Passes when the value is even.
 *
 * @internal
 */
class DocumentedRuleStub {}

class UndocumentedRuleStub {}

/**
 * @internal
 */
class TagOnlyRuleStub {}
