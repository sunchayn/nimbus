/**
 * Maps common property name patterns to generator IDs
 */
export const PROPERTY_NAME_PATTERNS: Array<{
    pattern: RegExp;
    generatorId: string;
    generatorConfig?: object;
    // Whether this generator can be used for integers. Defaults to false.
    isInteger?: boolean;
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

    // System
    { pattern: /^(version|v|semver)$/i, generatorId: 'semver' },

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
