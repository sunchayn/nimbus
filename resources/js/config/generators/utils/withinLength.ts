import type { GeneratorContext } from '@/interfaces/ui';
import { faker } from '@faker-js/faker/locale/en';

const MAX_ATTEMPTS = 5;

interface WithinLengthOptions {
    // Cuts a value that is too long. Use it for values that stay meaningful when cut.
    truncate?: boolean;
}

/**
 * Builds a string that respects the length constraints of the context.
 *
 * The producer runs again when its value does not fit. If it never fits,
 * a plain letters string of the required length is returned.
 */
export function withinLength(
    context: GeneratorContext | undefined,
    produce: () => string,
    options: WithinLengthOptions = {},
): string {
    const minLength = context?.minLength ?? 0;
    const maxLength = context?.maxLength ?? Infinity;

    for (let attempt = 0; attempt < MAX_ATTEMPTS; attempt++) {
        let value = produce();

        if (options.truncate && value.length > maxLength) {
            value = value.slice(0, maxLength);
        }

        if (value.length >= minLength && value.length <= maxLength) {
            return value;
        }
    }

    const max = Number.isFinite(maxLength) ? maxLength : Math.max(minLength, 1);

    return faker.string.alpha({
        length: { min: Math.min(Math.max(minLength, 1), max), max },
    });
}
