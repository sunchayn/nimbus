<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Schemas\RulesMapper;

use Illuminate\Validation\Rule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Schemas\Enums\SchemaPropertyType;
use Sunchayn\Nimbus\Modules\Schemas\Services\RulesMapper\Processors\EnumRuleProcessor;
use Sunchayn\Nimbus\Modules\Schemas\Services\RulesMapper\Processors\InRuleProcessor;
use Sunchayn\Nimbus\Modules\Schemas\Services\RulesMapper\RuleToSchemaMapper;

#[CoversClass(RuleToSchemaMapper::class)]
#[CoversClass(EnumRuleProcessor::class)]
#[CoversClass(InRuleProcessor::class)]
class RuleToSchemaMapperUnitTest extends TestCase
{
    public function test_it_maps_string_and_object_rules(): void
    {
        // Arrange

        $mapper = new RuleToSchemaMapper;

        // Act

        $attributes = $mapper->convertRulesToBaseSchemaPropertyMetadata(['required', 'string', 'size:10', 'in:', null]);

        $this->assertEquals([
            'required' => true,
            'type' => SchemaPropertyType::STRING,
            'minimum' => '10',
            'maximum' => '10',
        ], [
            'required' => $attributes['required'] ?? false,
            'type' => $attributes['type'] ?? null,
            'minimum' => $attributes['minimum'] ?? null,
            'maximum' => $attributes['maximum'] ?? null,
        ]);
    }

    public function test_it_handles_in_rule_with_integers_strings_and_empty_values(): void
    {
        // Arrange

        $inRuleWithInts = Rule::in([10, 20, 30]);
        $inRuleWithStrings = Rule::in(['active', 'inactive']);
        $inRuleWithEmpty = Rule::in([]);

        // Act

        $intResult = InRuleProcessor::process($inRuleWithInts);
        $stringResult = InRuleProcessor::process($inRuleWithStrings);
        $emptyResult = InRuleProcessor::process($inRuleWithEmpty);

        // Assert

        $this->assertEquals([
            'type' => SchemaPropertyType::INTEGER,
            'enum' => [10, 20, 30],
        ], $intResult);

        $this->assertEquals([
            'type' => SchemaPropertyType::STRING,
            'enum' => ['active', 'inactive'],
        ], $stringResult);

        $this->assertEquals([
            'type' => SchemaPropertyType::STRING,
            'enum' => null,
        ], $emptyResult);
    }

    public function test_it_handles_enum_rule_processing(): void
    {
        // Arrange

        $validEnumRule = Rule::enum(\Sunchayn\Nimbus\Tests\App\Modules\Schemas\Services\Builders\Stubs\StatusEnumStub::class);
        $invalidEnumRule = new \Illuminate\Validation\Rules\Enum('NonExistentEnumClass');
        $emptyEnumRule = new \Illuminate\Validation\Rules\Enum(\Sunchayn\Nimbus\Tests\App\Modules\Schemas\RulesMapper\EmptyUnitEnum::class);

        // Act

        $validResult = EnumRuleProcessor::process($validEnumRule);
        $invalidResult = EnumRuleProcessor::process($invalidEnumRule);
        $emptyResult = EnumRuleProcessor::process($emptyEnumRule);

        // Assert

        $this->assertEquals([
            'type' => SchemaPropertyType::STRING,
            'enum' => ['inactive', 'active'],
        ], $validResult);

        $this->assertEquals([
            'type' => SchemaPropertyType::STRING,
            'enum' => null,
        ], $invalidResult);

        $this->assertEquals([
            'type' => SchemaPropertyType::STRING,
            'enum' => null,
        ], $emptyResult);
    }
}

enum EmptyUnitEnum {}
