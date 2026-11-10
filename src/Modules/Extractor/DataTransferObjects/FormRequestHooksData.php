<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Extractor\DataTransferObjects;

/**
 * What a form request says about itself outside rules(), read without running it.
 */
final readonly class FormRequestHooksData
{
    /**
     * @param  string[]  $mergedKeys  Keys added by prepareForValidation(), so the client does not have to send them.
     * @param  array<string, string>  $labels  Field labels from attributes().
     * @param  bool  $authorizeReturnsFalse  Whether authorize() returns the literal false.
     */
    public function __construct(
        public array $mergedKeys = [],
        public array $labels = [],
        public bool $authorizeReturnsFalse = false,
    ) {}
}
