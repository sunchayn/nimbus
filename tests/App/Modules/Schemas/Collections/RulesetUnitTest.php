<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Schemas\Collections;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Schemas\Collections\Ruleset;

#[CoversClass(Ruleset::class)]
class RulesetUnitTest extends TestCase
{
    public function test_it_throws_exception_when_items_are_not_arrays(): void
    {
        // Anticiapte

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ruleset items must be an array');

        // Act

        new Ruleset(['field' => 'string_not_array']);
    }

    public function test_it_filters_fields_by_structure_types(): void
    {
        // Arrange

        $ruleset = Ruleset::fromLaravelRules([
            'name' => 'required|string',
            'user.profile' => 'required',
            'tags.*' => 'string',
        ]);

        // Act

        $rootFields = $ruleset->whereRootField();
        $dotNotationFields = $ruleset->whereDotNotationField();
        $arrayOfPrimitivesFields = $ruleset->whereArrayOfPrimitivesField();

        // Assert

        $this->assertEquals(['name'], array_keys($rootFields->all()));
        $this->assertEquals(['user.profile'], array_keys($dotNotationFields->all()));
        $this->assertEquals(['tags.*'], array_keys($arrayOfPrimitivesFields->all()));
    }
}
