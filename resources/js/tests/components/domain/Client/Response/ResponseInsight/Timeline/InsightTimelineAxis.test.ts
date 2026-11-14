import InsightTimelineAxis from '@/components/domain/Client/Response/ResponseInsight/Timeline/InsightTimelineAxis.vue';
import { mount } from '@vue/test-utils';
import { useElementSize } from '@vueuse/core';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick, ref } from 'vue';

// The width of the track is measured in the browser, so a test sets it by hand.
vi.mock('@vueuse/core', async importOriginal => {
    const { ref } = await import('vue');

    const width = ref(0);

    return {
        ...(await importOriginal<Record<string, unknown>>()),
        useElementSize: () => ({ width, height: ref(0) }),
    };
});

const setTrackWidth = (px: number) => {
    useElementSize(ref(null)).width.value = px;
};

const ticks = [
    { percent: 0, label: '0 ms' },
    { percent: 50, label: '50 ms' },
    { percent: 100, label: '100 ms' },
];

const render = (props: Record<string, unknown> = {}) =>
    mount(InsightTimelineAxis, { props: { ticks, ...props } });

describe('InsightTimelineAxis', () => {
    beforeEach(() => {
        setTrackWidth(0);
    });

    it('labels each tick at its place on the axis', () => {
        const labels = render().findAll('[data-testid="insight-axis-tick"]');

        expect(labels.map(label => label.text())).toEqual(['0 ms', '50 ms', '100 ms']);
        expect(labels.map(label => label.attributes('style'))).toEqual([
            'left: 0%;',
            'left: 50%;',
            'left: 100%;',
        ]);
    });

    it('keeps the labels of the edges inside the axis', () => {
        const labels = render().findAll('[data-testid="insight-axis-tick"]');

        expect(labels[0].classes()).not.toContain('-translate-x-full');
        expect(labels[0].classes()).not.toContain('-translate-x-1/2');
        expect(labels[1].classes()).toContain('-translate-x-1/2');
        expect(labels[2].classes()).toContain('-translate-x-full');
    });

    it('is hidden from assistive technology, since the rows carry the numbers', () => {
        expect(render().attributes('aria-hidden')).toBe('true');
    });

    it('reports the measured width of its track', async () => {
        setTrackWidth(320);

        const wrapper = render();

        await nextTick();

        expect(wrapper.emitted('update:width')?.at(-1)).toEqual([320]);
    });

    it('reports a new width when the track is resized', async () => {
        const wrapper = render();

        setTrackWidth(150);
        await nextTick();

        expect(wrapper.emitted('update:width')?.at(-1)).toEqual([150]);
    });
});
