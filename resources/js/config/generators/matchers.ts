import type { GeneratorContext, ValueGenerator } from '@/interfaces/ui';
import { getAllGenerators } from './registry';

export interface NameMatcher {
    pattern: RegExp;
    generator: ValueGenerator;
    defaultParameters?: GeneratorContext;
    returnsInteger: boolean;
}

const nameRules: NameMatcher[] = [];
const formatRules = new Map<string, ValueGenerator>();

for (const generator of getAllGenerators) {
    for (const rule of generator.nameRules ?? []) {
        nameRules.push({
            pattern: rule.pattern,
            generator,
            defaultParameters: rule.defaultParameters,
            returnsInteger: rule.returnsInteger ?? false,
        });
    }

    for (const rule of generator.typeRules ?? []) {
        formatRules.set(rule.format, generator);
    }
}

/**
 * Property name rules in category order. The first matching rule wins.
 */
export const nameMatchers: readonly NameMatcher[] = nameRules;

/**
 * Maps a JSON schema format to the generator that produces it.
 */
export const formatMatchers: ReadonlyMap<string, ValueGenerator> = formatRules;
