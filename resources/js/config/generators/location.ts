import type { ValueGenerator } from '@/interfaces/ui';
import { faker } from '@faker-js/faker';

/**
 * Location generators for creating various location-related values.
 */
export const locationGenerators: ValueGenerator[] = [
    {
        id: 'address',
        name: 'Address',
        description: 'Generate a random address',
        category: { id: 'location', name: 'Location' },
        generate: () => faker.location.streetAddress(),
        icon: 'i-hugeicons-location-01',
    },
    {
        id: 'city',
        name: 'City',
        description: 'Generate a random city',
        category: { id: 'location', name: 'Location' },
        generate: () => faker.location.city(),
        icon: 'i-hugeicons-location-01',
    },
    {
        id: 'country',
        name: 'Country',
        description: 'Generate a random country',
        category: { id: 'location', name: 'Location' },
        generate: () => faker.location.country(),
        icon: 'i-hugeicons-location-01',
    },
    {
        id: 'country-geocode',
        name: 'Country Geocode',
        description: 'Generate a random country geocode',
        category: { id: 'location', name: 'Location' },
        generate: () => faker.location.countryCode(),
        icon: 'i-hugeicons-location-01',
    },
    {
        id: 'locale',
        name: 'Locale',
        description: 'Generate a random locale',
        category: { id: 'location', name: 'Location' },
        generate: (): string => {
            const locales = [
                'en-us',
                'en-gb',
                'fr-fr',
                'fr-br',
                'de-de',
                'es-es',

                'it-it',
                'nl',
                'pt-pt',
                'pt-br',
                'zh-cn',
                'zh-tw',
                'ja-jp',
            ];

            return faker.helpers.arrayElement(locales);
        },
        icon: 'i-hugeicons-translate',
    },
    {
        id: 'state',
        name: 'State',
        description: 'Generate a random state',
        category: { id: 'location', name: 'Location' },
        generate: () => faker.location.state(),
        icon: 'i-hugeicons-location-01',
    },
    {
        id: 'zip-code',
        name: 'ZIP Code',
        description: 'Generate a random ZIP code',
        category: { id: 'location', name: 'Location' },
        generate: () => faker.location.zipCode(),
        icon: 'i-hugeicons-location-01',
    },
];
