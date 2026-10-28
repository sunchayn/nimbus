import { useTimeAgo, type UseTimeAgoMessages, type UseTimeAgoReturn } from '@vueuse/core';
import { type MaybeRefOrGetter } from 'vue';

const withAffix = (affix: (n: string) => string) => (n: string | number) =>
    /\d/.test(String(n)) ? affix(String(n)) : String(n);

const messages: UseTimeAgoMessages = {
    justNow: 'just now',
    past: withAffix(n => `${n} ago`),
    future: withAffix(n => `in ${n}`),
    invalid: '',
    second: n => `${n}s`,
    minute: n => `${n}m`,
    hour: n => `${n}h`,
    day: n => `${n}d`,
    week: n => `${n}w`,
    month: n => `${n}mo`,
    year: n => `${n}y`,
};

/**
 * Relative time with short units, such as 1h ago or 4m ago.
 */
export const useShortTimeAgo = (
    time: MaybeRefOrGetter<Date | number | string>,
): UseTimeAgoReturn<false> => useTimeAgo(time, { messages });
