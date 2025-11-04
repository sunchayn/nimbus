import { ValueGenerator } from '@/interfaces/ui';
import { faker } from '@faker-js/faker';
import {
    BarChart3Icon,
    BookOpenIcon,
    BuildingIcon,
    CalendarDaysIcon,
    CalendarIcon,
    ClockIcon,
    CodeIcon,
    CreditCardIcon,
    DatabaseIcon,
    DollarSignIcon,
    FileIcon,
    FileTextIcon,
    FingerprintIcon,
    FlagIcon,
    GlobeIcon,
    HashIcon,
    ImageIcon,
    KeyIcon,
    LanguagesIcon,
    MailIcon,
    MapPinIcon,
    HashIcon as NumberIcon,
    PercentIcon,
    PhoneCallIcon,
    PhoneIcon,
    ClockIcon as TimeIcon,
    TypeIcon,
    UserIcon,
    NetworkIcon,
    ShellIcon,
    FileCogIcon,
} from 'lucide-vue-next';

/**
 * Generator categories configuration.
 *
 * Defines the available categories for organizing value generators,
 * each with a unique ID, display name, and icon component.
 */
export const generatorCategories = [
    { id: 'text', name: 'Text', icon: FileTextIcon },
    { id: 'numbers', name: 'Numbers', icon: HashIcon },
    { id: 'time', name: 'Time', icon: TimeIcon },
    { id: 'person', name: 'Person', icon: UserIcon },
    { id: 'contact', name: 'Contact', icon: PhoneIcon },
    { id: 'location', name: 'Location', icon: MapPinIcon },
    { id: 'finance', name: 'Finance', icon: DollarSignIcon },
    { id: 'internet', name: 'Internet', icon: GlobeIcon },
    { id: 'company', name: 'Company', icon: BuildingIcon },
    { id: 'identifiers', name: 'Identifiers', icon: CreditCardIcon },
    { id: 'data', name: 'Data', icon: BarChart3Icon },
    { id: 'system', name: 'System', icon: ShellIcon },
];

/**
 * Text generators for creating various text-based values.
 */
const textGenerators: ValueGenerator[] = [
    {
        id: 'word',
        name: 'Word',
        description: 'Generate a random word',
        category: { id: 'text', name: 'Text' },
        generate: () => faker.word.sample(),
        icon: BookOpenIcon,
    },
    {
        id: 'words',
        name: 'Words',
        description: 'Generate multiple random words',
        category: { id: 'text', name: 'Text' },
        generate: () => faker.word.words(3),
        icon: TypeIcon,
    },
    {
        id: 'words-separated',
        name: 'Words Comma Seperated',
        description: 'Generate multiple random words',
        category: { id: 'text', name: 'Text' },
        generate: () => faker.word.words(3).replaceAll(' ', ','),
        icon: TypeIcon,
    },
    {
        id: 'sentence',
        name: 'Sentence',
        description: 'Generate a random sentence',
        category: { id: 'text', name: 'Text' },
        generate: () => faker.lorem.sentence(),
        icon: FileIcon,
    },
    {
        id: 'paragraph',
        name: 'Paragraph',
        description: 'Generate a random paragraph',
        category: { id: 'text', name: 'Text' },
        generate: () => faker.lorem.paragraph(),
        icon: FileTextIcon,
    },
    {
        id: 'slug',
        name: 'Slug',
        description: 'Generate a URL-friendly slug',
        category: { id: 'text', name: 'Text' },
        generate: () => faker.lorem.slug(),
        icon: TypeIcon,
    },
];

/**
 * Number generators for creating various numeric values.
 */
const numberGenerators: ValueGenerator[] = [
    {
        id: 'int',
        name: 'Integer',
        description: 'Generate a random integer',
        category: { id: 'numbers', name: 'Numbers' },
        generate: config => faker.number.int(config ?? { min: 1, max: 1000 }),
        icon: NumberIcon,
    },
    {
        id: 'float',
        name: 'Float',
        description: 'Generate a random decimal number',
        category: { id: 'numbers', name: 'Numbers' },
        generate: config =>
            faker.number.float(config ?? { min: 0, max: 100, fractionDigits: 2 }),
        icon: PercentIcon,
    },
    {
        id: 'bigint',
        name: 'BigInt',
        description: 'Generate a random big integer',
        category: { id: 'numbers', name: 'Numbers' },
        generate: config => faker.number.bigInt(config ?? {}),
        icon: HashIcon,
    },
];

/**
 * Time generators for creating various time-based values.
 */
const timeGenerators: ValueGenerator[] = [
    {
        id: 'date',
        name: 'Date',
        description: 'Generate a random date',
        category: { id: 'time', name: 'Time' },
        generate: () => faker.date.anytime().toISOString().split('T')[0],
        icon: CalendarIcon,
    },
    {
        id: 'datetime',
        name: 'DateTime',
        description: 'Generate a random date and time',
        category: { id: 'time', name: 'Time' },
        generate: () => faker.date.anytime().toISOString(),
        icon: CalendarDaysIcon,
    },
    {
        id: 'timestamp',
        name: 'Timestamp',
        description: 'Generate a Unix timestamp',
        category: { id: 'time', name: 'Time' },
        generate: () => Math.floor(faker.date.anytime().getTime() / 1000),
        icon: ClockIcon,
    },
    {
        id: 'time',
        name: 'Time',
        description: 'Generate a random time',
        category: { id: 'time', name: 'Time' },
        generate: () => faker.date.anytime().toLocaleTimeString(),
        icon: ClockIcon,
    },
];

/**
 * Person generators for creating various person-related values.
 */
const personGenerators: ValueGenerator[] = [
    {
        id: 'first-name',
        name: 'First Name',
        description: 'Generate a random first name',
        category: { id: 'person', name: 'Person' },
        generate: () => faker.person.firstName(),
        icon: UserIcon,
    },
    {
        id: 'last-name',
        name: 'Last Name',
        description: 'Generate a random last name',
        category: { id: 'person', name: 'Person' },
        generate: () => faker.person.lastName(),
        icon: UserIcon,
    },
    {
        id: 'full-name',
        name: 'Full Name',
        description: 'Generate a random full name',
        category: { id: 'person', name: 'Person' },
        generate: () => faker.person.fullName(),
        icon: UserIcon,
    },
    {
        id: 'job-title',
        name: 'Job Title',
        description: 'Generate a random job title',
        category: { id: 'person', name: 'Person' },
        generate: () => faker.person.jobTitle(),
        icon: UserIcon,
    },
    {
        id: 'bio',
        name: 'Bio',
        description: 'Generate a random bio',
        category: { id: 'person', name: 'Person' },
        generate: () => faker.person.bio(),
        icon: UserIcon,
    },
];

/**
 * Contact generators for creating various contact-related values.
 */
const contactGenerators: ValueGenerator[] = [
    {
        id: 'email',
        name: 'Email',
        description: 'Generate a random email address',
        category: { id: 'contact', name: 'Contact' },
        generate: () => faker.internet.email().toLowerCase(),
        icon: MailIcon,
    },
    {
        id: 'phone',
        name: 'Phone',
        description: 'Generate a random phone number',
        category: { id: 'contact', name: 'Contact' },
        generate: () => faker.phone.number({ style: 'national' }),
        icon: PhoneCallIcon,
    },
    {
        id: 'mobile',
        name: 'Mobile',
        description: 'Generate a random mobile number',
        category: { id: 'contact', name: 'Contact' },
        generate: () => faker.phone.number(),
        icon: PhoneIcon,
    },
];

/**
 * Location generators for creating various location-related values.
 */
const locationGenerators: ValueGenerator[] = [
    {
        id: 'address',
        name: 'Address',
        description: 'Generate a random address',
        category: { id: 'location', name: 'Location' },
        generate: () => faker.location.streetAddress(),
        icon: MapPinIcon,
    },
    {
        id: 'city',
        name: 'City',
        description: 'Generate a random city',
        category: { id: 'location', name: 'Location' },
        generate: () => faker.location.city(),
        icon: MapPinIcon,
    },
    {
        id: 'country',
        name: 'Country',
        description: 'Generate a random country',
        category: { id: 'location', name: 'Location' },
        generate: () => faker.location.country(),
        icon: MapPinIcon,
    },
    {
        id: 'country-geocode',
        name: 'Country Geocode',
        description: 'Generate a random country geocode',
        category: { id: 'location', name: 'Location' },
        generate: () => faker.location.countryCode(),
        icon: MapPinIcon,
    },
    {
        id: 'locale',
        name: 'Locale',
        description: 'Generate a random locale',
        category: { id: 'location', name: 'Location' },
        generate: () => {
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
        icon: LanguagesIcon,
    },
    {
        id: 'state',
        name: 'State',
        description: 'Generate a random state',
        category: { id: 'location', name: 'Location' },
        generate: () => faker.location.state(),
        icon: MapPinIcon,
    },
    {
        id: 'zip-code',
        name: 'ZIP Code',
        description: 'Generate a random ZIP code',
        category: { id: 'location', name: 'Location' },
        generate: () => faker.location.zipCode(),
        icon: MapPinIcon,
    },
];

/**
 * Finance generators for creating various finance-related values.
 */
const financeGenerators: ValueGenerator[] = [
    {
        id: 'amount',
        name: 'Amount',
        description: 'Generate a random monetary amount',
        category: { id: 'finance', name: 'Finance' },
        generate: config => faker.finance.amount(config ?? {}),
        icon: DollarSignIcon,
    },
    {
        id: 'currency',
        name: 'Currency',
        description: 'Generate a random currency code',
        category: { id: 'finance', name: 'Finance' },
        generate: () => faker.finance.currencyCode(),
        icon: DollarSignIcon,
    },
    {
        id: 'credit-card-number',
        name: 'Credit Card Number',
        description: 'Generate a random credit card number',
        category: { id: 'finance', name: 'Finance' },
        generate: () => faker.finance.creditCardNumber(),
        icon: CreditCardIcon,
    },
    {
        id: 'account',
        name: 'Account Number',
        description: 'Generate a random account number',
        category: { id: 'finance', name: 'Finance' },
        generate: () => faker.finance.accountNumber(),
        icon: CreditCardIcon,
    },
    {
        id: 'iban',
        name: 'IBAN',
        description: 'Generate a random IBAN',
        category: { id: 'finance', name: 'Finance' },
        generate: () => faker.finance.iban(),
        icon: CreditCardIcon,
    },
];

/**
 * Internet generators for creating various internet-related values.
 */
const internetGenerators: ValueGenerator[] = [
    {
        id: 'url',
        name: 'URL',
        description: 'Generate a random URL',
        category: { id: 'internet', name: 'Internet' },
        generate: () => faker.internet.url(),
        icon: GlobeIcon,
    },
    {
        id: 'domain',
        name: 'Domain',
        description: 'Generate a random domain name',
        category: { id: 'internet', name: 'Internet' },
        generate: () => faker.internet.domainName(),
        icon: GlobeIcon,
    },
    {
        id: 'username',
        name: 'Username',
        description: 'Generate a random username',
        category: { id: 'internet', name: 'Internet' },
        generate: () => faker.internet.userName(),
        icon: GlobeIcon,
    },
    {
        id: 'password',
        name: 'Password',
        description: 'Generate a random password',
        category: { id: 'internet', name: 'Internet' },
        generate: config => faker.internet.password(config ?? {}),
        icon: GlobeIcon,
    },
    {
        id: 'ipv4',
        name: 'IP V4',
        description: 'Generate a random IP v4',
        category: { id: 'internet', name: 'Internet' },
        generate: () => faker.internet.ipv4(),
        icon: NetworkIcon,
    },
    {
        id: 'ipv6',
        name: 'IP V6',
        description: 'Generate a random IP v6',
        category: { id: 'internet', name: 'Internet' },
        generate: () => faker.internet.ipv6(),
        icon: NetworkIcon,
    },
];

/**
 * Company generators for creating various company-related values.
 */
const companyGenerators: ValueGenerator[] = [
    {
        id: 'company-name',
        name: 'Company Name',
        description: 'Generate a random company name',
        category: { id: 'company', name: 'Company' },
        generate: () => faker.company.name(),
        icon: BuildingIcon,
    },
    {
        id: 'catch-phrase',
        name: 'Catch Phrase',
        description: 'Generate a random company catch phrase',
        category: { id: 'company', name: 'Company' },
        generate: () => faker.company.catchPhrase(),
        icon: BuildingIcon,
    },
    {
        id: 'bs',
        name: 'Buzzword',
        description: 'Generate random business buzzwords',
        category: { id: 'company', name: 'Company' },
        generate: () => faker.company.buzzPhrase(),
        icon: BuildingIcon,
    },
];

/**
 * Identifier generators for creating various identifier values.
 */
const identifierGenerators: ValueGenerator[] = [
    {
        id: 'uuid',
        name: 'UUID',
        description: 'Generate a UUID v4',
        category: { id: 'identifiers', name: 'Identifiers' },
        generate: () => faker.string.uuid(),
        icon: FingerprintIcon,
    },
    {
        id: 'nanoid',
        name: 'NanoID',
        description: 'Generate a NanoID',
        category: { id: 'identifiers', name: 'Identifiers' },
        generate: () => faker.string.nanoid(),
        icon: KeyIcon,
    },
    {
        id: 'alphanumeric',
        name: 'Alphanumeric',
        description: 'Generate random alphanumeric string',
        category: { id: 'identifiers', name: 'Identifiers' },
        generate: () => faker.string.alphanumeric(10),
        icon: HashIcon,
    },
];

/**
 * Data generators for creating various data-related values.
 */
const dataGenerators: ValueGenerator[] = [
    {
        id: 'json',
        name: 'JSON',
        description: 'Generate a random JSON object',
        category: { id: 'data', name: 'Data' },
        generate: () =>
            JSON.stringify(
                {
                    id: faker.number.int(),
                    name: faker.person.fullName(),
                    email: faker.internet.email().toLowerCase(),
                    createdAt: faker.date.anytime().toISOString(),
                },
                null,
                2,
            ),
        icon: CodeIcon,
    },
    {
        id: 'base64',
        name: 'Base64',
        description: 'Generate base64 encoded string',
        category: { id: 'data', name: 'Data' },
        generate: () => btoa(faker.string.alphanumeric(20)),
        icon: DatabaseIcon,
    },
    {
        id: 'hex',
        name: 'Hex',
        description: 'Generate a random hex string',
        category: { id: 'data', name: 'Data' },
        generate: () => faker.string.hexadecimal(),
        icon: DatabaseIcon,
    },
    {
        id: 'dataUri',
        name: 'Data URI',
        description: 'Generate a random data uri',
        category: { id: 'data', name: 'Data' },
        generate: () => btoa(faker.image.dataUri()),
        icon: ImageIcon,
    },
    {
        id: 'boolean-as-string',
        name: 'Boolean (As string)',
        description: 'Generate a random boolean',
        category: { id: 'data', name: 'Data' },
        generate: () => String(faker.datatype.boolean()),
        icon: FlagIcon,
    },
];

/**
 * System generators for creating various system values.
 */
const systemGenerators: ValueGenerator[] = [
    {
        id: 'semver',
        name: 'Semantic Version',
        description: 'Generate a semantic version',
        category: { id: 'system', name: 'System' },
        generate: () => faker.system.semver(),
        icon: FingerprintIcon,
    },
    {
        id: 'extension',
        name: 'File Extension',
        description: 'Generate a file extension',
        category: { id: 'system', name: 'System' },
        generate: () => faker.system.fileExt(),
        icon: FileCogIcon,
    },
];

/**
 * All available value generators organized by category.
 *
 * This array contains all generators from all categories,
 * providing a single source of truth for available generators.
 */
export const allValueGenerators: ValueGenerator[] = [
    ...textGenerators,
    ...numberGenerators,
    ...timeGenerators,
    ...personGenerators,
    ...contactGenerators,
    ...locationGenerators,
    ...financeGenerators,
    ...internetGenerators,
    ...companyGenerators,
    ...identifierGenerators,
    ...dataGenerators,
    ...systemGenerators,
];

/**
 * Maps common property name patterns to generator IDs
 */
export const PROPERTY_NAME_PATTERNS: Array<{
    pattern: RegExp;
    generatorId: string;
    generatorConfig?: object;
    isInteger?: boolean; // <- Tells whether this generator can be used for integers or not. Default: false.
}> = [
    // Person
    {
        pattern: /^(first[-_]?name|firstname|fname)$/i,
        generatorId: 'first-name',
    },
    {
        pattern: /^(last[-_]?name|lastname|surname|lname)$/i,
        generatorId: 'last-name',
    },
    { pattern: /^(full[-_]?name|fullname|name)$/i, generatorId: 'full-name' },
    {
        pattern: /^(job[-_]?title|title|position|role)$/i,
        generatorId: 'job-title',
    },
    { pattern: /^(bio|biography|about)$/i, generatorId: 'bio' },
    {
        pattern: /^age$/i,
        generatorId: 'int',
        generatorConfig: { min: 1, max: 100 },
        isInteger: true,
    },

    // Contact
    { pattern: /^(email|e[-_]?mail)$/i, generatorId: 'email' },
    { pattern: /^(phone|telephone|tel)$/i, generatorId: 'phone' },
    { pattern: /^(mobile|cell|cellular)$/i, generatorId: 'mobile' },

    // Location
    {
        pattern: /^(street|address|street[-_]?address)$/i,
        generatorId: 'address',
    },
    { pattern: /^(city|town)$/i, generatorId: 'city' },
    { pattern: /^(state)$/i, generatorId: 'state' },
    { pattern: /^(country|nation)$/i, generatorId: 'country' },
    {
        pattern: /^((country|nation)[-_]?)?(geocode|code)$/i,
        generatorId: 'country-geocode',
    },
    {
        pattern: /^(zip|zipcode|zip[-_]?code|postal[-_]?code|postcode)$/i,
        generatorId: 'zip-code',
    },
    {
        pattern: /^(region|locale)[-_]?(geocode|code)?$/i,
        generatorId: 'locale',
    },

    // Finance
    { pattern: /^(amount|price|cost|value|total)$/i, generatorId: 'amount' },
    { pattern: /^(currency|currency[-_]?code)$/i, generatorId: 'currency' },
    { pattern: /^(account|account[-_]?number|acct)$/i, generatorId: 'account' },
    { pattern: /^(iban)$/i, generatorId: 'iban' },
    {
        pattern: /^(cvv)$/i,
        generatorId: 'int',
        generatorConfig: { min: 100, max: 999 },
        isInteger: true,
    },
    {
        pattern: /^(card)?[-_]?number$/i,
        generatorId: 'credit-card-number',
        isInteger: false,
    },

    // Internet
    { pattern: /^(.+[-_]?)?(url|website|link)$/i, generatorId: 'url' },
    { pattern: /^(domain|domain[-_]?name)$/i, generatorId: 'domain' },
    { pattern: /^(username|user[-_]?name|login)$/i, generatorId: 'username' },
    { pattern: /^(password|pass|pwd)$/i, generatorId: 'password' },
    { pattern: /^(ip|ip[-_]?v4)$/i, generatorId: 'ipv4' },
    { pattern: /^(ip[-_]?v6)$/i, generatorId: 'ipv6' },

    // Database
    { pattern: /^(.+[-_])?(image|file|blob)s?$/i, generatorId: 'dataUri' },

    // Company
    {
        pattern: /^(company|company[-_]?name|organization|org)$/i,
        generatorId: 'company-name',
    },
    {
        pattern: /^(catch[-_]?phrase|slogan|tagline)$/i,
        generatorId: 'catch-phrase',
    },
    { pattern: /^(buzzword|buzz[-_]?phrase)$/i, generatorId: 'bs' },

    // Identifiers
    { pattern: /^(uuid|guid|id)$/i, generatorId: 'uuid' },
    { pattern: /^(nanoid|nano[-_]?id)$/i, generatorId: 'nanoid' },
    { pattern: /^(code|token|key)$/i, generatorId: 'alphanumeric' },
    { pattern: /^.+[-_]id$/i, generatorId: 'uuid' },

    // Time
    { pattern: /^(date)$/i, generatorId: 'date' },
    { pattern: /^(datetime|date[-_]?time)$/i, generatorId: 'datetime' },
    {
        pattern: /^(.+[-_])?timestamp$/i,
        generatorId: 'timestamp',
        isInteger: true,
    },
    { pattern: /^(time)$/i, generatorId: 'time' },
    {
        pattern: /^(.+[-_])?year$/i,
        generatorId: 'int',
        generatorConfig: { min: 1, max: 3000 },
        isInteger: true,
    },
    {
        pattern: /^(.+[-_])?month/i,
        generatorId: 'int',
        generatorConfig: { min: 1, max: 12 },
        isInteger: true,
    },
    {
        pattern: /^(.+[-_])?day$/i,
        generatorId: 'int',
        generatorConfig: { min: 1, max: 31 },
        isInteger: true,
    },

    // Text
    { pattern: /^(word|tag|keyword)$/i, generatorId: 'word' },
    {
        pattern: /^(.+[-_])?(words|tags|keywords)$/i,
        generatorId: 'words-separated',
    },
    {
        pattern: /^(.+[-_])?(sentence|text|description)s?$/i,
        generatorId: 'sentence',
    },
    {
        pattern: /^(.+[-_])?(paragraph|content|body|message)s?$/i,
        generatorId: 'sentence',
    },
    { pattern: /^(slug)$/i, generatorId: 'slug' },

    // Other
    {
        pattern: /^(page)$/i,
        generatorId: 'int',
        generatorConfig: { min: 1, max: 99 },
        isInteger: true,
    },
    {
        pattern: /^(is|has|was)(.+[-_])?.+$/i,
        generatorId: 'boolean-as-string', // <- We want it a string here, if the request has it boolean then it won't reach this step.
    },
    {
        pattern: /^per_page$/i,
        generatorId: 'int',
        generatorConfig: { min: 25, max: 100 },
        isInteger: true,
    },
];
