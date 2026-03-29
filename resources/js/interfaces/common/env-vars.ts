/**
 * Types and interfaces related to environment variable resolution and UI representation.
 */

/**
 * Global pattern for environment variable placeholders: {{variable_name}}
 */
export const PLACEHOLDER_PATTERN = /\{\{([^}]+)\}\}/g;

/**
 * Possible resolution status of an environment variable placeholder.
 */
export enum EnvVariableCheckStatus {
    Resolved = 'resolved',
    Missing = 'missing',
    Empty = 'empty',
    None = 'none',
}

/**
 * Represents a segment of a string that is either plain text or an environment variable.
 */
export interface StringSegment {
    isEnvVariable: boolean;
    text: string;
    status: EnvVariableCheckStatus;
    resolvedValue: string | null;
}

/**
 * A resolver function that takes a raw string (potentially containing environment variable placeholders)
 * and returns the resolved value.
 */
export type ResolverFn = (value: string | number | boolean | null | undefined) => string;
