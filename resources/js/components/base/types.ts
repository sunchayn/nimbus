/**
 * Mirrors the helper types Nuxt UI uses for its generic item lists, which it does not export.
 */
export type ArrayOrNested<T> = T[] | T[][];
export type NestedItem<T> = T extends Array<infer I> ? NestedItem<I> : T;
