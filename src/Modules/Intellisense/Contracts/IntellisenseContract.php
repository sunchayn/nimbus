<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Intellisense\Contracts;

/**
 * Contract for intellisense generators that create TypeScript types.
 *
 * Each intellisense generator is responsible for converting backend data structures
 * into frontend TypeScript definitions for better type safety and developer experience.
 */
interface IntellisenseContract
{
    /**
     * Returns the target filename for the generated intellisense file.
     *
     * The filename should be descriptive and follow TypeScript naming conventions.
     */
    public function getTargetFileName(): string;

    /**
     * Generates the intellisense content as TypeScript code.
     *
     * The generated content should be valid TypeScript that can be imported
     * and used in frontend applications for type safety and autocompletion.
     */
    public function generate(): string;
}
