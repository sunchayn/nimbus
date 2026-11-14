import InsightGates from '@/components/domain/Client/Response/ResponseInsight/Gates/InsightGates.vue';
import type { InsightGate } from '@/interfaces/http';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const makeGate = (overrides: Partial<InsightGate> = {}): InsightGate => ({
    ability: 'view',
    result: true,
    arguments: [],
    user: null,
    ...overrides,
});

const render = (gates: InsightGate[]) => mount(InsightGates, { props: { gates } });

describe('InsightGates', () => {
    it('lists denied checks first', () => {
        const wrapper = render([
            makeGate({ ability: 'view', result: true }),
            makeGate({ ability: 'delete', result: false }),
        ]);

        const rows = wrapper.findAll('[data-testid="insight-gate"]');

        expect(rows[0].text()).toContain('delete');
        expect(rows[0].text()).toContain('Denied');
        expect(rows[1].text()).toContain('Allowed');
    });

    it('says when a check made no decision', () => {
        const wrapper = render([makeGate({ result: null })]);

        expect(wrapper.find('[data-testid="insight-gate"]').text()).toContain(
            'No decision',
        );
    });

    it('puts a dot only on a denied check', () => {
        const wrapper = render([
            makeGate({ result: true }),
            makeGate({ result: false }),
            makeGate({ result: null }),
        ]);

        const dots = wrapper
            .findAll('[data-testid="insight-gate"]')
            .map(row => row.find('[data-testid="insight-flag-dot"]').exists());

        // Denied checks are listed first.
        expect(dots).toEqual([true, false, false]);
    });

    it('shows the arguments and the user of a check', () => {
        const wrapper = render([makeGate({ arguments: ['Post', '5'], user: 'User #1' })]);

        const row = wrapper.find('[data-testid="insight-gate"]');

        expect(row.text()).toContain('(Post, 5)');
        expect(row.text()).toContain('for User #1');
    });

    it('shows no details for a check without arguments or user', () => {
        const wrapper = render([makeGate()]);

        const text = wrapper.find('[data-testid="insight-gate"]').text();

        expect(text).not.toContain('(');
        expect(text).not.toContain('for ');
    });

    it('shows a note when no gate was checked', () => {
        expect(render([]).text()).toContain('No gate was checked.');
    });
});
