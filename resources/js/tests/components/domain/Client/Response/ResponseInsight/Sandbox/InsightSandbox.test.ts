import InsightSandbox from '@/components/domain/Client/Response/ResponseInsight/Sandbox/InsightSandbox.vue';
import type { InsightSandbox as Sandbox } from '@/interfaces/http';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const render = (sandbox: Sandbox) => mount(InsightSandbox, { props: { sandbox } });

const full: Sandbox = {
    mail: [{ subject: 'Welcome', to: ['a@b.test'], body: '<p>Hi</p>' }],
    notifications: [
        {
            notification: 'App\\Notice',
            notifiable: 'App\\User#1',
            channels: ['mail', 'sms'],
        },
    ],
    jobs: [{ job: 'App\\Jobs\\Ship', queue: 'default' }],
};

describe('InsightSandbox', () => {
    it('says the request ran in sandbox mode', () => {
        expect(render(full).text()).toContain('ran with the sandbox mode ON');
    });

    it('shows a mail with its subject, recipients, and body', () => {
        const mail = render(full).find('[data-testid="sandbox-mail"]');

        expect(mail.text()).toContain('Welcome');
        expect(mail.text()).toContain('To a@b.test');
        expect(mail.find('[data-testid="sandbox-mail-body"]').text()).toBe('<p>Hi</p>');
    });

    it('opens a lone mail and keeps one of several closed', async () => {
        const several = render({
            mail: [
                { subject: 'A', to: ['a@b.test'], body: 'first' },
                { subject: 'B', to: ['a@b.test'], body: 'second' },
            ],
        });

        expect(render(full).find('[data-testid="sandbox-mail-body"]').exists()).toBe(
            true,
        );
        expect(several.find('[data-testid="sandbox-mail-body"]').exists()).toBe(false);

        await several.findAll('[data-testid="insight-card-trigger"]')[1].trigger('click');

        expect(several.find('[data-testid="sandbox-mail-body"]').text()).toBe('second');
    });

    it('shows a notification with who got it and the channels', () => {
        const item = render(full).find('[data-testid="sandbox-notification"]');

        expect(item.text()).toContain('App\\Notice');
        expect(item.text()).toContain('To App\\User#1');
        expect(
            item.findAll('[data-testid="sandbox-channel"]').map(badge => badge.text()),
        ).toEqual(['mail', 'sms']);
    });

    it('shows a job with its queue', () => {
        const item = render(full).find('[data-testid="sandbox-job"]');

        expect(item.text()).toContain('App\\Jobs\\Ship');
        expect(item.text()).toContain('default');
    });

    it('counts the items of each group', () => {
        const wrapper = render(full);

        expect(wrapper.find('[data-testid="sandbox-mail-group"] h3').text()).toBe(
            'Mail 1',
        );
        expect(wrapper.find('[data-testid="sandbox-notification-group"] h3').text()).toBe(
            'Notifications 1',
        );
        expect(wrapper.find('[data-testid="sandbox-job-group"] h3').text()).toBe(
            'Queued jobs 1',
        );
    });

    it('shows a note for a sandboxed effect that captured nothing', () => {
        const wrapper = render({ mail: [], jobs: [] });

        expect(wrapper.find('[data-testid="sandbox-mail-group"]').text()).toContain(
            'No mail was sent.',
        );
        expect(wrapper.find('[data-testid="sandbox-job-group"]').text()).toContain(
            'No job was queued.',
        );
    });

    it('leaves out the groups of effects that were not sandboxed', () => {
        const wrapper = render({ jobs: [] });

        expect(wrapper.find('[data-testid="sandbox-mail-group"]').exists()).toBe(false);
        expect(wrapper.find('[data-testid="sandbox-notification-group"]').exists()).toBe(
            false,
        );
    });
});
