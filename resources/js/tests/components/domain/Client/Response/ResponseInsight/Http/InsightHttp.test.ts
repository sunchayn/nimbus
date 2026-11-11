import { AppBadge } from '@/components/base/badge';
import InsightHttp from '@/components/domain/Client/Response/ResponseInsight/Http/InsightHttp.vue';
import type { InsightHttpEntry } from '@/interfaces/http';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { makeHttp } from '../helpers';

const render = (entries: InsightHttpEntry[], dropped?: number) =>
    mount(InsightHttp, { props: { entries, dropped } });

describe('InsightHttp', () => {
    it('shows the status, method, and URL of a call', () => {
        const wrapper = render([
            makeHttp({ method: 'POST', url: 'https://example.com/hook', status: 500 }),
        ]);

        const call = wrapper.find('[data-testid="insight-http-call"]');

        expect(call.find('[data-testid="insight-http-status"]').text()).toBe('500');
        expect(call.text()).toContain('POST');
        expect(call.text()).toContain('https://example.com/hook');
    });

    it('keeps the whole URL in a title, since the line is cut short', () => {
        const wrapper = render([
            makeHttp({ url: 'https://example.com/a/very/long/path' }),
        ]);

        expect(wrapper.find('[title]').attributes('title')).toBe(
            'https://example.com/a/very/long/path',
        );
    });

    it.each([
        [200, 'success'],
        [301, 'info'],
        [404, 'warning'],
        [500, 'error'],
    ])('colors a %s status as %s', (status, color) => {
        const wrapper = render([makeHttp({ status })]);

        expect(wrapper.findComponent(AppBadge).props('color')).toBe(color);
    });

    it('shows a call that got no response as failed', () => {
        const wrapper = render([makeHttp({ status: null, duration_ms: null })]);

        const status = wrapper.find('[data-testid="insight-http-status"]');

        expect(status.text()).toBe('Failed');
        expect(wrapper.findComponent(AppBadge).props('color')).toBe('error');
    });

    it('shows the duration only when it is known', () => {
        const known = render([makeHttp({ duration_ms: 12 })]);
        const unknown = render([makeHttp({ duration_ms: null })]);

        expect(known.text()).toContain('12 ms');
        expect(unknown.text()).not.toContain('ms');
    });

    it('tells how many calls were not shown', () => {
        const wrapper = render([makeHttp()], 5);

        expect(wrapper.find('[data-testid="insight-dropped"]').text()).toContain('5');
    });
});
