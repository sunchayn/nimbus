<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Schemas\Services\RulesMapper\Processors;

/**
 * Processes rules that make a field optional, nullable, conditional, or forbidden.
 */
class ConditionalRuleProcessor
{
    private const CONDITIONAL_PATTERN = '/^(required|prohibited|exclude|present|missing)(?:_(if|unless|with|without|with_all|without_all))?$/';

    /**
     * @param  string[]  $params
     * @return array{nullable?: true, sometimes?: true, skipAutofill?: true, hints?: string[]}
     */
    public static function process(string $name, array $params): array
    {
        if ($name === 'nullable') {
            return ['nullable' => true];
        }

        if ($name === 'sometimes') {
            return ['sometimes' => true];
        }

        if (preg_match(self::CONDITIONAL_PATTERN, $name, $matches) !== 1) {
            return [];
        }

        $kind = $matches[1];
        $condition = $matches[2] ?? null;

        // Plain required and present rules need no label, the mapper reads required itself.
        if ($condition === null && in_array($kind, ['required', 'present'], true)) {
            return [];
        }

        $isForbidden = in_array($kind, ['prohibited', 'exclude', 'missing'], true);

        $updates = ['hints' => [self::describe($kind, $condition, $params)]];

        return $isForbidden ? $updates + ['skipAutofill' => true] : $updates;
    }

    /**
     * Builds a short label such as "required if role is admin".
     *
     * @param  string[]  $params
     */
    private static function describe(string $kind, ?string $condition, array $params): string
    {
        $label = $kind;

        if ($condition === null) {
            return $label;
        }

        return match ($condition) {
            'if' => sprintf('%s if %s is %s', $label, $params[0] ?? '?', implode(', ', array_slice($params, 1))),
            'unless' => sprintf('%s unless %s is %s', $label, $params[0] ?? '?', implode(', ', array_slice($params, 1))),
            'with' => sprintf('%s with %s', $label, implode(' or ', $params)),
            'without' => sprintf('%s without %s', $label, implode(' or ', $params)),
            'with_all' => sprintf('%s with all of %s', $label, implode(', ', $params)),
            default => sprintf('%s without all of %s', $label, implode(', ', $params)),
        };
    }
}
