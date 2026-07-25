<?php

namespace Sunchayn\Nimbus\Tests\App\Modules\Routes\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use Sunchayn\Nimbus\Modules\Config\GlobalHeaderGeneratorTypeEnum;
use Sunchayn\Nimbus\Modules\Routes\Actions\BuildGlobalHeadersAction;
use Sunchayn\Nimbus\Tests\TestCase;

#[CoversClass(BuildGlobalHeadersAction::class)]
class BuildGlobalHeadersActionFunctionalTest extends TestCase
{
    public function test_it_builds_global_headers(): void
    {
        // Arrange

        $globalHeadersConfig = [
            'x-request-id' => GlobalHeaderGeneratorTypeEnum::Uuid,
            'x-author-email' => GlobalHeaderGeneratorTypeEnum::Email,
            'x-author-id' => GlobalHeaderGeneratorTypeEnum::String,
            'X-Custom-Header' => '::value::',
        ];

        $this->mock(\Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver::class, function (\Mockery\MockInterface $mock) use ($globalHeadersConfig) {
            $mock->shouldReceive('getHeaders')->andReturn($globalHeadersConfig);
        });

        $action = resolve(BuildGlobalHeadersAction::class);

        // Act

        $headers = $action->execute();

        // Assert

        $this->assertEquals(
            [
                [
                    'header' => 'x-request-id',
                    'type' => 'generator',
                    'value' => 'UUID',
                ],
                [
                    'header' => 'x-author-email',
                    'type' => 'generator',
                    'value' => 'Email',
                ],
                [
                    'header' => 'x-author-id',
                    'type' => 'generator',
                    'value' => 'String',
                ],
                [
                    'header' => 'X-Custom-Header',
                    'type' => 'raw',
                    'value' => '::value::',
                ],
            ],
            $headers
        );
    }

    public function test_it_works_without_headers(): void
    {
        // Arrange

        $this->mock(\Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver::class, function (\Mockery\MockInterface $mock) {
            $mock->shouldReceive('getHeaders')->andReturn([]);
        });

        $action = resolve(BuildGlobalHeadersAction::class);

        // Act

        $headers = $action->execute();

        // Assert

        $this->assertEmpty($headers);
    }

    public function test_it_builds_global_headers_with_string_aliases(): void
    {
        // Arrange

        $globalHeadersConfig = [
            'x-request-id' => '$uuid',
            'x-author-email' => '$email',
            'x-author-id' => '$string',
            'X-Custom-Header' => '::value::',
        ];

        $this->mock(\Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver::class, function (\Mockery\MockInterface $mock) use ($globalHeadersConfig) {
            $mock->shouldReceive('getHeaders')->andReturn($globalHeadersConfig);
        });

        $action = resolve(BuildGlobalHeadersAction::class);

        // Act

        $headers = $action->execute();

        // Assert

        $this->assertEquals(
            [
                [
                    'header' => 'x-request-id',
                    'type' => 'generator',
                    'value' => 'UUID',
                ],
                [
                    'header' => 'x-author-email',
                    'type' => 'generator',
                    'value' => 'Email',
                ],
                [
                    'header' => 'x-author-id',
                    'type' => 'generator',
                    'value' => 'String',
                ],
                [
                    'header' => 'X-Custom-Header',
                    'type' => 'raw',
                    'value' => '::value::',
                ],
            ],
            $headers
        );
    }
}
