export enum ValueGeneratorCommandOpenMethod {
    CLICK = 'click',
    SHIFT_SHIFT = 'shift-shift',
}

/**
 * Optional hints a caller can pass to a generator.
 *
 * Each generator reads only the fields it supports.
 */
export interface GeneratorContext {
    min?: number;
    max?: number;
    minLength?: number;
    maxLength?: number;
    fractionDigits?: number;
}

/**
 * Picks a generator when the schema property name matches the pattern.
 */
export interface PropertyNameBasedRule {
    pattern: RegExp;
    // Whether the generator returns an integer for this rule. Defaults to false.
    returnsInteger?: boolean;
    // Parameters used for the generator when the schema does not set them.
    defaultParameters?: GeneratorContext;
}

/**
 * Picks a generator when the schema property has the given JSON schema format.
 */
export interface PropertyTypeBasedRule {
    format: string;
}

export interface ValueGenerator {
    id: string;
    name: string;
    description: string;
    category: GeneratorCategory;
    generate: (context?: GeneratorContext) => string | number | bigint;
    nameRules?: PropertyNameBasedRule[];
    typeRules?: PropertyTypeBasedRule[];
    shortcut?: string;
    icon?: string;
}

export interface GeneratorCategory {
    id: string;
    name: string;
    icon?: string;
}

export interface GeneratorCommandState {
    searchQuery: string;
    selectedCategory: string | null;
    recentGenerators: string[];
}
