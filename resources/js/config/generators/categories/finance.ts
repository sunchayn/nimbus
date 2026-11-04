import { faker } from '@faker-js/faker/locale/en';
import { defineCategory } from '../utils/defineCategory';
import { withinLength } from '../utils/withinLength';

export const finance = defineCategory({
    id: 'finance',
    name: 'Finance',
    icon: 'i-hugeicons-dollar-01',
    generators: [
        {
            id: 'amount',
            name: 'Amount',
            description: 'Generate a random monetary amount',
            icon: 'i-hugeicons-money-bag-01',
            generate: context =>
                faker.finance.amount({ min: context?.min, max: context?.max }),
            nameRules: [{ pattern: /^(amount|price|cost|value|total)$/i }],
        },
        {
            id: 'currency',
            name: 'Currency',
            description: 'Generate a random currency code',
            icon: 'i-hugeicons-coins-01',
            generate: context =>
                withinLength(context, () => faker.finance.currencyCode()),
            nameRules: [{ pattern: /^(currency|currency[-_]?code)$/i }],
        },
        {
            id: 'credit-card-number',
            name: 'Credit Card Number',
            icon: 'i-hugeicons-credit-card',
            generate: context =>
                withinLength(context, () => faker.finance.creditCardNumber(), {
                    truncate: true,
                }),
            nameRules: [{ pattern: /^(card)?[-_]?number$/i }],
        },
        {
            id: 'account',
            name: 'Account Number',
            icon: 'i-hugeicons-wallet-01',
            generate: context =>
                withinLength(context, () =>
                    faker.finance.accountNumber(
                        context?.maxLength ?? Math.max(context?.minLength ?? 0, 8),
                    ),
                ),
            nameRules: [{ pattern: /^(account|account[-_]?number|acct)$/i }],
        },
        {
            id: 'iban',
            name: 'IBAN',
            icon: 'i-hugeicons-bank',
            generate: context =>
                withinLength(context, () => faker.finance.iban(), { truncate: true }),
            nameRules: [{ pattern: /^(iban)$/i }],
        },
    ],
});
