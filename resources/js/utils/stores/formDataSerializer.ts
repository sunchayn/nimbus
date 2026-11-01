import { RequestBodyTypeEnum } from '@/interfaces/http';
import type { StateTree } from 'pinia';

const FORM_DATA_TAG = '__formData';

type StoredEntry = string | { file: string };

const revive = (key: string, value: unknown): unknown => {
    if (value === null || typeof value !== 'object') {
        return value;
    }

    if (FORM_DATA_TAG in value) {
        const formData = new FormData();

        (value[FORM_DATA_TAG] as [string, StoredEntry][]).forEach(([name, entry]) => {
            formData.append(
                name,
                typeof entry === 'string' ? entry : new File([], entry.file),
            );
        });

        return formData;
    }

    // Older versions saved form data as an empty object.
    return key === RequestBodyTypeEnum.FORM_DATA && !Array.isArray(value) ? null : value;
};

/**
 * Persists a store state as JSON while keeping FormData values.
 * JSON turns FormData into an empty object, so it is tagged and rebuilt on load.
 * File contents are not kept, a file is restored as an empty file with the same name.
 */
export const formDataSerializer = {
    serialize: (state: unknown): string =>
        JSON.stringify(state, (_key, value) => {
            if (!(value instanceof FormData)) {
                return value;
            }

            const entries: [string, StoredEntry][] = Array.from(value.entries()).map(
                ([key, entry]) => [
                    key,
                    entry instanceof File ? { file: entry.name } : entry,
                ],
            );

            return { [FORM_DATA_TAG]: entries };
        }),

    deserialize: (raw: string): StateTree => {
        try {
            return JSON.parse(raw, revive);
        } catch {
            // State that cannot be restored is dropped, so a broken save never breaks the app.
            return {};
        }
    },
};
