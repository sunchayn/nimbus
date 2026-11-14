import InsightFlag from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightFlag.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const dot = (tone?: 'error' | 'warning' | 'success' | 'primary' | 'muted' | null) =>
    mount(InsightFlag, { props: { tone }, slots: { default: 'Label' } }).find(
        '[data-testid="insight-flag-dot"]',
    );

describe('InsightFlag', () => {
    it('shows its text', () => {
        const wrapper = mount(InsightFlag, { slots: { default: 'N+1' } });

        expect(wrapper.text()).toBe('N+1');
    });

    it.each([
        ['error', 'bg-error'],
        ['warning', 'bg-warning'],
        ['success', 'bg-success'],
        ['primary', 'bg-primary'],
        ['muted', 'bg-primary/40'],
    ] as const)('draws a %s tone as %s', (tone, expected) => {
        expect(dot(tone).classes()).toContain(expected);
        expect(dot(tone).attributes('data-tone')).toBe(tone);
    });

    it.each([[undefined], [null]])('draws no dot without a tone (%s)', tone => {
        expect(dot(tone).exists()).toBe(false);
    });
});
