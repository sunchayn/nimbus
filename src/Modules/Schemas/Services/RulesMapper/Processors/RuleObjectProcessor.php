<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Schemas\Services\RulesMapper\Processors;

use Closure;
use Illuminate\Validation\Rules\ProhibitedIf;
use Illuminate\Validation\Rules\RequiredIf;
use ReflectionClass;
use ReflectionFunction;

/**
 * Processes rule objects whose behavior is code, so only a label and what is safe to read are kept.
 *
 * Nothing here runs a rule or a condition closure. A closure becomes a hint with its source line.
 */
class RuleObjectProcessor
{
    /**
     * @return array{required?: true, hints?: string[]}
     */
    public static function requiredIf(RequiredIf $rule): array
    {
        $condition = $rule->condition;

        if ($condition instanceof Closure) {
            return ['hints' => ['required if a closure passes, '.self::describeClosure($condition)]];
        }

        return $condition ? ['required' => true] : [];
    }

    /**
     * @return array{skipAutofill?: true, hints?: string[]}
     */
    public static function prohibitedIf(ProhibitedIf $rule): array
    {
        $condition = $rule->condition;

        if ($condition instanceof Closure) {
            return ['hints' => ['prohibited if a closure passes, '.self::describeClosure($condition)]];
        }

        return $condition ? ['hints' => ['prohibited'], 'skipAutofill' => true] : [];
    }

    /**
     * @return array{hints: string[]}
     */
    public static function closure(Closure $rule): array
    {
        return ['hints' => ['custom closure rule, '.self::describeClosure($rule)]];
    }

    /**
     * Uses the class name and the first line of its docblock, and never reads validate().
     *
     * @return array{hints: string[]}
     */
    public static function custom(object $rule): array
    {
        $reflectionClass = new ReflectionClass($rule);

        $summary = self::docBlockSummary($reflectionClass->getDocComment());

        return ['hints' => [trim('custom rule '.$reflectionClass->getShortName().($summary === null ? '' : ', '.$summary))]];
    }

    public static function describeClosure(Closure $closure): string
    {
        $reflectionFunction = new ReflectionFunction($closure);

        return sprintf('defined in %s line %d', basename((string) $reflectionFunction->getFileName()), $reflectionFunction->getStartLine());
    }

    private static function docBlockSummary(string|false $docBlock): ?string
    {
        if ($docBlock === false) {
            return null;
        }

        foreach (explode("\n", $docBlock) as $line) {
            $line = trim($line, " \t*/");

            if ($line !== '' && ! str_starts_with($line, '@')) {
                return $line;
            }
        }

        return null;
    }
}
