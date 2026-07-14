<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/../../src',
        __DIR__.'/..',
    ]);

    $rectorConfig->skip([
        __DIR__.'/../phpstan/build',
    ]);

    $rectorConfig->rules([
        TypedPropertyFromStrictConstructorRector::class,
    ]);

    $rectorConfig->sets([
        SetList::PHP_82,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::CODING_STYLE,
        SetList::NAMING,
        SetList::PRIVATIZATION,
        SetList::TYPE_DECLARATION,
    ]);

    $rectorConfig->skip([
        \Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector::class => [
            __DIR__.'/../../src/Modules/Relay/Responses/DumpAndDieResponse.php', // <- has a breaking change BC fix.
        ],
    ]);

};
