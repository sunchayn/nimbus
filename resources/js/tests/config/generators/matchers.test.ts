import { formatMatchers, getAllGenerators, nameMatchers } from '@/config/generators';
import { describe, expect, it } from 'vitest';

const matchName = (name: string, integer = false): string | undefined =>
    nameMatchers.find(
        matcher => (!integer || matcher.returnsInteger) && matcher.pattern.test(name),
    )?.generator.id;

const matchingIds = (name: string): string[] => [
    ...new Set(
        nameMatchers
            .filter(matcher => matcher.pattern.test(name))
            .map(matcher => matcher.generator.id),
    ),
];

describe('generator matchers', () => {
    describe('Registry', () => {
        it('has unique generator ids', () => {
            // Arrange

            const ids = getAllGenerators.map(generator => generator.id);

            // Assert

            expect(new Set(ids).size).toBe(ids.length);
        });

        it('gives every generator a description and an icon', () => {
            // Assert

            for (const generator of getAllGenerators) {
                expect(generator.description).toBeTruthy();
                expect(generator.icon).toBeTruthy();
            }
        });

        it('maps schema formats to generators', () => {
            // Assert

            expect(formatMatchers.get('uuid')?.id).toBe('uuid');
            expect(formatMatchers.get('uri')?.id).toBe('url');
            expect(formatMatchers.get('date-time')?.id).toBe('datetime');
        });
    });

    describe('Fixed rules', () => {
        it('maps a bare code to the alphanumeric generator', () => {
            expect(matchName('code')).toBe('alphanumeric');
        });

        it.each(['country_code', 'countryCode', 'nation-code', 'geocode'])(
            'maps %s to the country geocode generator',
            name => {
                expect(matchName(name)).toBe('country-geocode');
            },
        );

        it.each(['is_active', 'isActive', 'has_children', 'hasChildren', 'was_deleted'])(
            'maps %s to a boolean string',
            name => {
                expect(matchName(name)).toBe('boolean-as-string');
            },
        );

        it.each(['issue', 'hash', 'island', 'washer', 'history'])(
            'does not map %s to a boolean string',
            name => {
                expect(matchName(name)).not.toBe('boolean-as-string');
            },
        );

        it.each(['url', 'website', 'profile_url', 'profileUrl', 'home-link'])(
            'maps %s to the url generator',
            name => {
                expect(matchName(name)).toBe('url');
            },
        );

        it('does not map curl to the url generator', () => {
            expect(matchName('curl')).toBeUndefined();
        });

        it('only maps month when it ends the name', () => {
            expect(matchName('month', true)).toBe('int');
            expect(matchName('birth_month', true)).toBe('int');
            expect(matchName('monthly_fee', true)).toBeUndefined();
        });

        it('keeps the numeric default parameters', () => {
            const age = nameMatchers.find(matcher => matcher.pattern.test('age'));
            const cvv = nameMatchers.find(matcher => matcher.pattern.test('cvv'));

            expect(age?.defaultParameters).toEqual({ min: 1, max: 100 });
            expect(cvv?.defaultParameters).toEqual({ min: 100, max: 999 });
        });
    });

    describe('Collisions', () => {
        it.each([
            'first_name',
            'last_name',
            'name',
            'title',
            'email',
            'phone',
            'mobile',
            'address',
            'city',
            'state',
            'country',
            'country_code',
            'zip',
            'locale',
            'amount',
            'currency',
            'account',
            'iban',
            'cvv',
            'card_number',
            'url',
            'domain',
            'username',
            'password',
            'ip',
            'company',
            'slogan',
            'id',
            'user_id',
            'uuid',
            'code',
            'token',
            'date',
            'datetime',
            'timestamp',
            'created_timestamp',
            'time',
            'year',
            'month',
            'day',
            'word',
            'tags',
            'description',
            'notes',
            'body',
            'slug',
            'version',
            'page',
            'per_page',
            'is_active',
            'avatar_image',
        ])('lets a single generator claim %s', name => {
            expect(matchingIds(name)).toHaveLength(1);
        });
    });
});
