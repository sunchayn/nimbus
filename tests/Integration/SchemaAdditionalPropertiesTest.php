<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Schemas\SchemaConfig;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\ObjectSchemaProperty;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\Schema;
use Sunchayn\Nimbus\Modules\Schemas\ValueObjects\StringSchemaProperty;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(SchemaConfig::class)]
#[CoversClass(Schema::class)]
#[CoversClass(ObjectSchemaProperty::class)]
class SchemaAdditionalPropertiesTest extends TestCase
{
    private const CONFIG_KEY = 'nimbus.schemas.additional_properties';

    protected function tearDown(): void
    {
        if ($this->app) {
            $this->app['config']->set(self::CONFIG_KEY, false);
        }

        parent::tearDown();
    }

    public function test_it_emits_strict_schemas_by_default(): void
    {
        // Act

        $root = $this->makeSchema()->toJsonSchema();
        $nested = $this->makeNestedObjectProperty()->toJsonSchema();

        // Assert

        $this->assertFalse($root['additionalProperties']);
        $this->assertFalse($nested['additionalProperties']);
    }

    public function test_it_emits_permissive_schemas_when_config_is_enabled(): void
    {
        // Arrange

        $this->app['config']->set(self::CONFIG_KEY, true);

        // Act

        $root = $this->makeSchema()->toJsonSchema();
        $nested = $this->makeNestedObjectProperty()->toJsonSchema();

        // Assert

        $this->assertTrue($root['additionalProperties']);
        $this->assertTrue($nested['additionalProperties']);
    }

    public function test_it_honors_explicit_object_flag_when_config_is_disabled(): void
    {
        // Arrange

        $this->app['config']->set(self::CONFIG_KEY, false);

        // Act

        $nested = new ObjectSchemaProperty(
            name: 'meta',
            schema: new Schema([new StringSchemaProperty('note')]),
            additionalProperties: true,
        );

        // Assert

        $this->assertTrue($nested->toJsonSchema()['additionalProperties']);
    }

    private function makeSchema(): Schema
    {
        return new Schema([
            new StringSchemaProperty('name'),
        ]);
    }

    private function makeNestedObjectProperty(): ObjectSchemaProperty
    {
        return new ObjectSchemaProperty(
            name: 'meta',
            schema: new Schema([new StringSchemaProperty('note')]),
        );
    }
}
