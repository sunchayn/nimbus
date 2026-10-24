import type { ValueGenerator } from '@/interfaces/ui';
import { faker } from '@faker-js/faker';

/**
 * Finance generators for creating various finance-related values.
 */
export const financeGenerators: ValueGenerator[] = [
    {
        id: 'amount',
        name: 'Amount',
        description: 'Generate a random monetary amount',
        category: { id: 'finance', name: 'Finance' },
        generate: config => faker.finance.amount(config ?? {}),
        icon: 'i-hugeicons-dollar-01',
    },
    {
        id: 'currency',
        name: 'Currency',
        description: 'Generate a random currency code',
        category: { id: 'finance', name: 'Finance' },
        generate: () => faker.finance.currencyCode(),
        icon: 'i-hugeicons-dollar-01',
    },
    {
        id: 'credit-card-number',
        name: 'Credit Card Number',
        description: 'Generate a random credit card number',
        category: { id: 'finance', name: 'Finance' },
        generate: () => faker.finance.creditCardNumber(),
        icon: 'i-hugeicons-credit-card',
    },
    {
        id: 'account',
        name: 'Account Number',
        description: 'Generate a random account number',
        category: { id: 'finance', name: 'Finance' },
        generate: () => faker.finance.accountNumber(),
        icon: 'i-hugeicons-credit-card',
    },
    {
        id: 'iban',
        name: 'IBAN',
        description: 'Generate a random IBAN',
        category: { id: 'finance', name: 'Finance' },
        generate: () => faker.finance.iban(),
        icon: 'i-hugeicons-credit-card',
    },
];
