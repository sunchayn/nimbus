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

    $rectorConfig
        ->sets([
            SetList::PHP_82,
            SetList::CODE_QUALITY,
            SetList::DEAD_CODE,
            SetList::CODING_STYLE,
            SetList::PRIVATIZATION,
            SetList::TYPE_DECLARATION,
        ]);
};
