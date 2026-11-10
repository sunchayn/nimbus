const DAYS = [
    'Sunday',
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
];

const MONTHS = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
];

const pad = (value: number, length = 2): string => String(value).padStart(length, '0');

const isLeapYear = (year: number): boolean =>
    (year % 4 === 0 && year % 100 !== 0) || year % 400 === 0;

const dayOfYear = (date: Date): number =>
    Math.floor(
        (Date.UTC(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate()) -
            Date.UTC(date.getUTCFullYear(), 0, 1)) /
            86400000,
    );

// Returns the ISO 8601 week number and the year that week belongs to.
function isoWeek(date: Date): { week: number; year: number } {
    const thursday = new Date(
        Date.UTC(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate()),
    );
    thursday.setUTCDate(thursday.getUTCDate() + 4 - (thursday.getUTCDay() || 7));

    const year = thursday.getUTCFullYear();
    const week = Math.ceil(
        ((thursday.getTime() - Date.UTC(year, 0, 1)) / 86400000 + 1) / 7,
    );

    return { week, year };
}

function ordinalSuffix(day: number): string {
    if (day >= 11 && day <= 13) {
        return 'th';
    }

    return ['th', 'st', 'nd', 'rd'][day % 10] ?? 'th';
}

const TOKENS: Record<string, (date: Date) => string> = {
    d: date => pad(date.getUTCDate()),
    D: date => DAYS[date.getUTCDay()].slice(0, 3),
    j: date => String(date.getUTCDate()),
    l: date => DAYS[date.getUTCDay()],
    N: date => String(date.getUTCDay() || 7),
    S: date => ordinalSuffix(date.getUTCDate()),
    w: date => String(date.getUTCDay()),
    z: date => String(dayOfYear(date)),
    W: date => pad(isoWeek(date).week),
    F: date => MONTHS[date.getUTCMonth()],
    m: date => pad(date.getUTCMonth() + 1),
    M: date => MONTHS[date.getUTCMonth()].slice(0, 3),
    n: date => String(date.getUTCMonth() + 1),
    t: date =>
        String(
            new Date(
                Date.UTC(date.getUTCFullYear(), date.getUTCMonth() + 1, 0),
            ).getUTCDate(),
        ),
    L: date => (isLeapYear(date.getUTCFullYear()) ? '1' : '0'),
    o: date => String(isoWeek(date).year),
    Y: date => String(date.getUTCFullYear()),
    y: date => pad(date.getUTCFullYear() % 100),
    a: date => (date.getUTCHours() < 12 ? 'am' : 'pm'),
    A: date => (date.getUTCHours() < 12 ? 'AM' : 'PM'),
    g: date => String(date.getUTCHours() % 12 || 12),
    G: date => String(date.getUTCHours()),
    h: date => pad(date.getUTCHours() % 12 || 12),
    H: date => pad(date.getUTCHours()),
    i: date => pad(date.getUTCMinutes()),
    s: date => pad(date.getUTCSeconds()),
    u: date => pad(date.getUTCMilliseconds() * 1000, 6),
    v: date => pad(date.getUTCMilliseconds(), 3),
    e: () => 'UTC',
    I: () => '0',
    O: () => '+0000',
    P: () => '+00:00',
    p: () => 'Z',
    T: () => 'UTC',
    Z: () => '0',
    c: date => formatPhpDate(date, 'Y-m-d\\TH:i:sP'),
    r: date => formatPhpDate(date, 'D, d M Y H:i:s O'),
    U: date => String(Math.floor(date.getTime() / 1000)),
};

/**
 * Formats a date the way the PHP date() function does, in UTC.
 *
 * A backslash makes the next character literal, as it does in PHP.
 */
export function formatPhpDate(date: Date, format: string): string {
    let result = '';

    for (let index = 0; index < format.length; index++) {
        const character = format[index];

        if (character === '\\') {
            index++;
            result += format[index] ?? '';
            continue;
        }

        result += TOKENS[character]?.(date) ?? character;
    }

    return result;
}
