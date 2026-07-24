<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\App\Modules\Extractor\Actions {
    use Generator;
    use Mockery;
    use PHPUnit\Framework\Attributes\CoversClass;
    use PHPUnit\Framework\Attributes\DataProvider;
    use PHPUnit\Framework\TestCase;
    use Sunchayn\Nimbus\Modules\Ast\Queries\ClassQuery;
    use Sunchayn\Nimbus\Modules\Extractor\Actions\InferEloquentModelFromJsonResourceAction;

    class DummyModelClass {}

    #[CoversClass(InferEloquentModelFromJsonResourceAction::class)]
    class InferEloquentModelFromJsonResourceActionUnitTest extends TestCase
    {
        #[DataProvider('modelResolutionProvider')]
        public function test_it_resolves_json_resource_model(
            string $docBlock,
            ?string $className,
            ?string $expectedModel
        ): void {
            // Arrange

            $action = new InferEloquentModelFromJsonResourceAction;

            $classQueryMock = Mockery::mock(ClassQuery::class);

            // Anticipate

            $classQueryMock
                ->shouldReceive('getClassDocBlock')
                ->andReturn($docBlock);

            if ($className !== null) {
                $classQueryMock
                    ->shouldReceive('className')
                    ->andReturn($className);
            }

            // Act

            $result = $action->execute($classQueryMock);

            // Assert

            $this->assertSame($expectedModel, $result);
        }

        public static function modelResolutionProvider(): Generator
        {
            yield 'resolves via property convention' => [
                'docBlock' => '/** @property \\'.DummyModelClass::class.' $resource */',
                'className' => null,
                'expectedModel' => DummyModelClass::class,
            ];

            yield 'resolves via mixin convention' => [
                'docBlock' => '/** @mixin \\'.DummyModelClass::class.' */',
                'className' => null,
                'expectedModel' => DummyModelClass::class,
            ];

            yield 'resolves via name convention' => [
                'docBlock' => '/** Nothing */',
                'className' => 'UserResource',
                'expectedModel' => \App\Models\User::class,
            ];

            yield 'returns null when no strategy matches' => [
                'docBlock' => '/** Nothing */',
                'className' => 'SomeNonExistentResource',
                'expectedModel' => null,
            ];
        }
    }
}

namespace App\Models {
    class User {}
}
