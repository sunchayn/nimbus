import InsightCache from '@/components/domain/Client/Response/ResponseInsight/Cache/InsightCache.vue';
import type { InsightCacheEntry } from '@/interfaces/http';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const render = (entries: InsightCacheEntry[], dropped?: number) =>
    mount(InsightCache, { props: { entries, dropped } });

describe('InsightCache', () => {
    it('lists each operation with its key', () => {
        const wrapper = render([
            { operation: 'hit', key: 'users' },
            { operation: 'write', key: 'posts' },
        ]);

        const entries = wrapper.findAll('[data-testid="insight-cache-entry"]');

        expect(entries).toHaveLength(2);
        expect(entries[0].text()).toContain('hit');
        expect(entries[0].text()).toContain('users');
        expect(entries[1].text()).toContain('write');
        expect(entries[1].text()).toContain('posts');
    });

    it.each([
        ['hit', 'bg-success'],
        ['miss', 'bg-primary/40'],
        ['write', 'bg-primary'],
        ['forget', 'bg-error'],
    ] as const)('draws the %s operation with a %s dot', (operation, dotClass) => {
        const wrapper = render([{ operation, key: 'k' }]);

        const dot = wrapper.find('[data-testid="insight-flag-dot"]');

        expect(dot.classes()).toContain(dotClass);
        expect(wrapper.find('[data-testid="insight-cache-operation"]').text()).toBe(
            operation,
        );
    });

    it('tells how many entries were not shown', () => {
        const wrapper = render([{ operation: 'hit', key: 'k' }], 3);

        expect(wrapper.find('[data-testid="insight-dropped"]').text()).toContain('3');
    });
});
