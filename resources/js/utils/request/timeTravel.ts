/**
 * Time travel values.
 *
 * Only two shapes are produced.
 * A relative form such as `+1 day -2 hours`, and an absolute date and time in the date time local form.
 * Both are parsed by Carbon on the host.
 */

export const TIME_TRAVEL_MIN_AMOUNT = 1;
export const TIME_TRAVEL_MAX_AMOUNT = 9999;
export const TIME_TRAVEL_MAX_PARTS = 6;

export type TimeTravelUnit =
    | 'second'
    | 'minute'
    | 'hour'
    | 'day'
    | 'week'
    | 'month'
    | 'year';

export type TimeTravelDirection = '+' | '-';

export interface RelativeTimeTravelPart {
    direction: TimeTravelDirection;
    amount: number;
    unit: TimeTravelUnit;
}

export interface TimeTravelPreset {
    label: string;
    description: string;
    value: string;
}

export const TIME_TRAVEL_PRESETS: TimeTravelPreset[] = [
    { label: '+1h', description: 'In 1 hour', value: '+1 hour' },
    { label: '+1d', description: 'Tomorrow', value: '+1 day' },
    { label: '+1w', description: 'In 1 week', value: '+1 week' },
    { label: '+1mo', description: 'In 1 month', value: '+1 month' },
    { label: '+1y', description: 'In 1 year', value: '+1 year' },
    { label: '-1d', description: 'Yesterday', value: '-1 day' },
    { label: '-1w', description: 'Last week', value: '-1 week' },
    { label: '-1mo', description: 'Last month', value: '-1 month' },
];

const UNIT_ALIASES: Record<string, TimeTravelUnit> = {
    s: 'second',
    sec: 'second',
    secs: 'second',
    second: 'second',
    seconds: 'second',
    m: 'minute',
    min: 'minute',
    mins: 'minute',
    minute: 'minute',
    minutes: 'minute',
    h: 'hour',
    hr: 'hour',
    hrs: 'hour',
    hour: 'hour',
    hours: 'hour',
    d: 'day',
    day: 'day',
    days: 'day',
    w: 'week',
    wk: 'week',
    wks: 'week',
    week: 'week',
    weeks: 'week',
    mo: 'month',
    mos: 'month',
    month: 'month',
    months: 'month',
    y: 'year',
    yr: 'year',
    yrs: 'year',
    year: 'year',
    years: 'year',
};

const PART_PATTERN = /([+-])?\s*(\d+)\s*([a-z]+)/y;
const ABSOLUTE_PATTERN = /^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})(?::\d{2})?$/;

/**
 * Whether an amount is a whole number the dialog accepts.
 */
export const isValidAmount = (amount: number): boolean =>
    Number.isInteger(amount) &&
    amount >= TIME_TRAVEL_MIN_AMOUNT &&
    amount <= TIME_TRAVEL_MAX_AMOUNT;

/**
 * Formats the parts such as `+1 day -2 hours`, using the singular for one.
 * Every part carries its own sign, because Carbon applies a sign to one part only.
 */
export const formatRelativeTimeTravel = (parts: RelativeTimeTravelPart[]): string =>
    parts
        .map(
            ({ direction, amount, unit }) =>
                `${direction}${amount} ${unit}${amount === 1 ? '' : 's'}`,
        )
        .join(' ');

/**
 * Reads free form text such as `+1h`, `-2 days` or `+1d 2h 30min`.
 * A part without a sign takes the sign of the part before it, and the first one is a plus.
 * Returns null when any piece of the text is not understood.
 */
export const parseRelativeTimeTravel = (
    text: string,
): RelativeTimeTravelPart[] | null => {
    const value = text.trim().toLowerCase();

    if (value === '') {
        return null;
    }

    const parts: RelativeTimeTravelPart[] = [];
    let direction: TimeTravelDirection = '+';
    let position = 0;

    while (position < value.length) {
        PART_PATTERN.lastIndex = position;
        const match = PART_PATTERN.exec(value);

        if (!match) {
            return null;
        }

        const amount = Number(match[2]);
        const unit = UNIT_ALIASES[match[3]];

        if (!unit || !isValidAmount(amount)) {
            return null;
        }

        direction = (match[1] as TimeTravelDirection | undefined) ?? direction;
        parts.push({ direction, amount, unit });
        position = PART_PATTERN.lastIndex;

        // Parts are separated by spaces or commas.
        while (/[\s,]/.test(value[position] ?? '')) {
            position++;
        }
    }

    return parts.length <= TIME_TRAVEL_MAX_PARTS ? parts : null;
};

/**
 * Reads an absolute date and time and returns it in the date time local form.
 * Returns null when the text is not a real date and time.
 */
export const parseAbsoluteTimeTravel = (value: string): string | null => {
    const match = ABSOLUTE_PATTERN.exec(value.trim());

    if (!match) {
        return null;
    }

    const [, year, month, day, hour, minute] = match.map(Number);
    const date = new Date(year, month - 1, day, hour, minute);

    const isRealDate =
        date.getFullYear() === year &&
        date.getMonth() === month - 1 &&
        date.getDate() === day &&
        date.getHours() === hour &&
        date.getMinutes() === minute;

    return isRealDate
        ? `${match[1]}-${match[2]}-${match[3]}T${match[4]}:${match[5]}`
        : null;
};

/**
 * Applies the parts to a date, one after the other.
 */
export const applyRelativeTimeTravel = (
    parts: RelativeTimeTravelPart[],
    from: Date = new Date(),
): Date => {
    const date = new Date(from);

    for (const { direction, amount, unit } of parts) {
        const signed = direction === '-' ? -amount : amount;

        switch (unit) {
            case 'second':
                date.setSeconds(date.getSeconds() + signed);
                break;
            case 'minute':
                date.setMinutes(date.getMinutes() + signed);
                break;
            case 'hour':
                date.setHours(date.getHours() + signed);
                break;
            case 'day':
                date.setDate(date.getDate() + signed);
                break;
            case 'week':
                date.setDate(date.getDate() + signed * 7);
                break;
            case 'month':
                date.setMonth(date.getMonth() + signed);
                break;
            case 'year':
                date.setFullYear(date.getFullYear() + signed);
                break;
        }
    }

    return date;
};

/**
 * Formats a date for the preview line.
 */
export const formatPreviewDate = (date: Date): string =>
    new Intl.DateTimeFormat(undefined, {
        dateStyle: 'full',
        timeStyle: 'medium',
    }).format(date);
