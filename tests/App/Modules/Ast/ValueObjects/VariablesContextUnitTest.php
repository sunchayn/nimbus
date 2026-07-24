<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Ast\ValueObjects;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sunchayn\Nimbus\Modules\Ast\ValueObjects\ArrayAstContextValue;
use Sunchayn\Nimbus\Modules\Ast\ValueObjects\ObjectAstContextValue;
use Sunchayn\Nimbus\Modules\Ast\ValueObjects\ScalarAstContextValue;
use Sunchayn\Nimbus\Modules\Ast\ValueObjects\VariablesContext;

#[CoversClass(VariablesContext::class)]
#[CoversClass(ScalarAstContextValue::class)]
#[CoversClass(ObjectAstContextValue::class)]
#[CoversClass(ArrayAstContextValue::class)]
class VariablesContextUnitTest extends TestCase
{
    public function test_it_stores_and_retrieves_variables(): void
    {
        // Arrange

        $scalar = new ScalarAstContextValue(value: 5, variableName: 'count');
        $obj = new ObjectAstContextValue(className: 'App\Models\User', variableName: 'user');
        $arr = new ArrayAstContextValue(value: ['a', 'b'], variableName: 'items');

        // Act

        $context = new VariablesContext([$scalar, $obj, $arr]);

        // Assert

        $this->assertTrue($context->has('count'));
        $this->assertTrue($context->has('user'));
        $this->assertTrue($context->has('items'));
        $this->assertFalse($context->has('unknown'));

        $this->assertSame($scalar, $context->get('count'));

        $this->assertSame('App\Models\User', $context->get('user')?->getValue());

        $this->assertEquals(['a', 'b'], $context->get('items')?->getValue());
    }

    public function test_it_creates_empty_context(): void
    {
        // Act

        $context = VariablesContext::empty();

        // Assert

        $this->assertEmpty($context->getItems());

        $this->assertEquals([], $context->toArray());
    }
}
