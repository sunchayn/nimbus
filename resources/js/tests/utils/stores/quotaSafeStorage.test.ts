import { quotaSafeStorage, resetQuotaNotice } from '@/utils/stores/quotaSafeStorage';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const warning = vi.hoisted(() => vi.fn());

vi.mock('@/components/base/toast', () => ({
    useAppToast: () => ({ warning }),
}));

const tabs = (responses: Record<string, unknown>) =>
    JSON.stringify({
        activeTabId: 'a',
        tabs: Object.entries(responses).map(([id, response]) => ({ id, response })),
    });

/**
 * Makes setItem throw while the whole storage holds more than the limit.
 */
const limitStorage = (limit: number) => {
    const original = Storage.prototype.setItem;

    vi.spyOn(Storage.prototype, 'setItem').mockImplementation(function (
        this: Storage,
        key: string,
        value: string,
    ) {
        const others = Object.keys(localStorage)
            .filter(name => name !== key)
            .reduce((sum, name) => sum + (localStorage.getItem(name) ?? '').length, 0);

        if (others + value.length > limit) {
            throw new DOMException('full', 'QuotaExceededError');
        }

        original.call(this, key, value);
    });
};

describe('quotaSafeStorage', () => {
    beforeEach(() => {
        vi.restoreAllMocks();
        localStorage.clear();
        warning.mockClear();
        resetQuotaNotice();
    });

    it('writes normally when there is room', () => {
        quotaSafeStorage.setItem('nimbus:tabs', 'x');

        expect(localStorage.getItem('nimbus:tabs')).toBe('x');
        expect(warning).not.toHaveBeenCalled();
    });

    it('drops responses of inactive tabs first and warns once', () => {
        const big = 'z'.repeat(500);
        localStorage.setItem(
            'nimbus:tabs',
            tabs({ a: { body: 'keep' }, b: { body: big } }),
        );
        limitStorage(400);

        quotaSafeStorage.setItem(
            'nimbus:tabs',
            tabs({ a: { body: 'keep' }, b: { body: big } }),
        );

        const stored = JSON.parse(localStorage.getItem('nimbus:tabs') ?? '');

        expect(stored.tabs[0].response).toEqual({ body: 'keep' });
        expect(stored.tabs[1].response).toBeNull();
        expect(warning).toHaveBeenCalledTimes(1);
    });

    it('drops the oldest history when dropping tab responses is not enough', () => {
        const logs = Array.from({ length: 8 }, (_, id) => ({
            id,
            body: 'y'.repeat(100),
        }));
        localStorage.setItem('nimbus:requestHistory', JSON.stringify({ logs }));
        limitStorage(500);

        quotaSafeStorage.setItem('nimbus:tabs', tabs({ a: null }));

        const stored = JSON.parse(localStorage.getItem('nimbus:requestHistory') ?? '');

        expect(stored.logs.length).toBeLessThan(8);
        expect(stored.logs.at(-1).id).toBe(7);
        expect(localStorage.getItem('nimbus:tabs')).not.toBeNull();
    });

    it('does not throw when nothing can be freed', () => {
        limitStorage(1);

        expect(() => quotaSafeStorage.setItem('other', 'too large')).not.toThrow();
        expect(warning).toHaveBeenCalledTimes(1);
    });
});
