import InsightEvents from '@/components/domain/Client/Response/ResponseInsight/Events/InsightEvents.vue';
import type { InsightEventEntry } from '@/interfaces/http';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const render = (entries: InsightEventEntry[], dropped?: number) =>
    mount(InsightEvents, { props: { entries, dropped } });

describe('InsightEvents', () => {
    it('lists each event by name', () => {
        const wrapper = render([
            { name: 'App\\Events\\Shipped' },
            { name: 'App\\Events\\Paid' },
        ]);

        const events = wrapper.findAll('[data-testid="insight-event"]');

        expect(events.map(event => event.text())).toEqual([
            'App\\Events\\Shipped',
            'App\\Events\\Paid',
        ]);
    });

    it('tells how many events were not shown', () => {
        const wrapper = render([{ name: 'App\\Events\\Shipped' }], 9);

        expect(wrapper.find('[data-testid="insight-dropped"]').text()).toContain('9');
    });
});
