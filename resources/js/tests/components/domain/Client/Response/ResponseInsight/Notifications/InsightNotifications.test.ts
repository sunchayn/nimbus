import InsightNotifications from '@/components/domain/Client/Response/ResponseInsight/Notifications/InsightNotifications.vue';
import type { InsightNotificationEntry } from '@/interfaces/http';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const render = (entries: InsightNotificationEntry[], dropped?: number) =>
    mount(InsightNotifications, { props: { entries, dropped } });

describe('InsightNotifications', () => {
    it('lists each notification with its channel', () => {
        const wrapper = render([
            { notification: 'App\\Notifications\\Shipped', channel: 'mail' },
            { notification: 'App\\Notifications\\Paid', channel: 'database' },
        ]);

        const items = wrapper.findAll('[data-testid="insight-notification"]');

        expect(items).toHaveLength(2);
        expect(items[0].text()).toContain('App\\Notifications\\Shipped');
        expect(items[0].text()).toContain('mail');
        expect(items[1].text()).toContain('database');
    });

    it('tells how many notifications were not shown', () => {
        const wrapper = render([{ notification: 'N', channel: 'mail' }], 6);

        expect(wrapper.find('[data-testid="insight-dropped"]').text()).toContain('6');
    });
});
