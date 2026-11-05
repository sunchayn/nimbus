import { BINARY_FIXTURES } from '@/config/binaries/fixtures';
import type { BinaryFixture } from '@/interfaces/ui';
import { faker } from '@faker-js/faker/locale/en';

export interface FixtureConstraints {
    // Only fixtures with one of these mime types can be picked.
    mimeTypes?: readonly string[];
    // The bounds apply to the encoded base64 string.
    minLength?: number;
    maxLength?: number;
}

/**
 * Picks a random fixture that satisfies the constraints.
 *
 * Returns undefined when no fixture fits.
 */
export function pickFixture(
    constraints: FixtureConstraints = {},
): BinaryFixture | undefined {
    const minLength = constraints.minLength ?? 0;
    const maxLength = constraints.maxLength ?? Infinity;

    const candidates = BINARY_FIXTURES.filter(function (fixture) {
        const fitsMimeTypes =
            constraints.mimeTypes === undefined ||
            constraints.mimeTypes.includes(fixture.mimeType);

        return (
            fitsMimeTypes &&
            fixture.base64.length >= minLength &&
            fixture.base64.length <= maxLength
        );
    });

    return candidates.length > 0 ? faker.helpers.arrayElement(candidates) : undefined;
}
