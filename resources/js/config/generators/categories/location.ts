import { faker } from '@faker-js/faker/locale/en';
import { defineCategory } from '../utils/defineCategory';
import { withinLength } from '../utils/withinLength';

const locales = [
    'en-us',
    'en-gb',
    'fr-fr',
    'fr-be',
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

export const location = defineCategory({
    id: 'location',
    name: 'Location',
    icon: 'i-hugeicons-location-01',
    generators: [
        {
            id: 'address',
            name: 'Address',
            icon: 'i-hugeicons-home-01',
            generate: context =>
                withinLength(context, () => faker.location.streetAddress()),
            nameRules: [{ pattern: /^(street|address|street[-_]?address)$/i }],
        },
        {
            id: 'city',
            name: 'City',
            icon: 'i-hugeicons-city-01',
            generate: context => withinLength(context, () => faker.location.city()),
            nameRules: [{ pattern: /^(city|town)$/i }],
        },
        {
            id: 'country',
            name: 'Country',
            icon: 'i-hugeicons-flag-02',
            generate: context => {
                // A short length can only be a country code.
                if (context?.maxLength !== undefined && context.maxLength <= 3) {
                    const code = context.maxLength === 3 ? 'alpha-3' : 'alpha-2';

                    return withinLength(context, () => faker.location.countryCode(code));
                }

                return withinLength(context, () => faker.location.country());
            },
            nameRules: [{ pattern: /^(country|nation)$/i }],
        },
        {
            id: 'country-geocode',
            name: 'Country Geocode',
            icon: 'i-hugeicons-map-pin',
            generate: context => {
                const code = (context?.minLength ?? 0) >= 3 ? 'alpha-3' : 'alpha-2';

                return withinLength(context, () => faker.location.countryCode(code));
            },
            // A bare "code" is too generic, so it needs a country prefix.
            nameRules: [{ pattern: /^((country|nation)[-_]?(geocode|code)|geocode)$/i }],
        },
        {
            id: 'locale',
            name: 'Locale',
            icon: 'i-hugeicons-translate',
            generate: context =>
                withinLength(context, () => faker.helpers.arrayElement(locales)),
            nameRules: [{ pattern: /^(region|locale)[-_]?(geocode|code)?$/i }],
        },
        {
            id: 'state',
            name: 'State',
            icon: 'i-hugeicons-flag-01',
            generate: context => {
                const abbreviated = (context?.maxLength ?? Infinity) <= 2;

                return withinLength(context, () => faker.location.state({ abbreviated }));
            },
            nameRules: [{ pattern: /^(state)$/i }],
        },
        {
            id: 'zip-code',
            name: 'ZIP Code',
            icon: 'i-hugeicons-map-pinpoint-01',
            generate: context =>
                withinLength(context, () => faker.location.zipCode(), { truncate: true }),
            nameRules: [
                { pattern: /^(zip|zipcode|zip[-_]?code|postal[-_]?code|postcode)$/i },
            ],
        },
    ],
});
