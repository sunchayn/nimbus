export interface KeyedItem<T> {
    item: T;
    key: string;
}

/**
 * Builds list keys from item content and how many times that content appeared before.
 * Use it for lists with no stable id, so a bare index is not needed.
 */
export const withOccurrenceKeys = <T>(
    items: readonly T[],
    getContent: (item: T) => string,
): KeyedItem<T>[] => {
    const seen = new Map<string, number>();

    return items.map(item => {
        const content = getContent(item);
        const occurrence = seen.get(content) ?? 0;

        seen.set(content, occurrence + 1);

        return { item, key: `${content}#${occurrence}` };
    });
};
