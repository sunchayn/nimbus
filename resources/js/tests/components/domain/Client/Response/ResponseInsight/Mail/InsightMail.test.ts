import InsightMail from '@/components/domain/Client/Response/ResponseInsight/Mail/InsightMail.vue';
import type { InsightMailEntry } from '@/interfaces/http';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const makeMail = (overrides: Partial<InsightMailEntry> = {}): InsightMailEntry => ({
    subject: 'Welcome',
    to: [],
    mailable: null,
    ...overrides,
});

const CARD = '[data-testid="insight-mail-message"]';

const render = (entries: InsightMailEntry[], dropped?: number) =>
    mount(InsightMail, { props: { entries, dropped } });

describe('InsightMail', () => {
    it('lists each message with its subject', () => {
        const wrapper = render([
            makeMail({ subject: 'Welcome' }),
            makeMail({ subject: 'Receipt' }),
        ]);

        const mails = wrapper.findAll(CARD);

        expect(mails).toHaveLength(2);
        expect(mails[0].text()).toContain('Welcome');
        expect(mails[1].text()).toContain('Receipt');
    });

    it('names a message without a subject', () => {
        const wrapper = render([makeMail({ subject: '' })]);

        expect(wrapper.find(CARD).text()).toContain('No subject');
    });

    it('shows the recipients and the mailable class', () => {
        const wrapper = render([
            makeMail({
                to: ['a@example.com', 'b@example.com'],
                mailable: 'App\\Mail\\Welcome',
            }),
        ]);

        const mail = wrapper.find(CARD);

        expect(mail.text()).toContain('To a@example.com, b@example.com');
        expect(mail.find('[data-testid="insight-mail-mailable"]').text()).toBe(
            'App\\Mail\\Welcome',
        );
    });

    it('shows no details for a message without recipients or mailable', () => {
        const wrapper = render([makeMail()]);

        const mail = wrapper.find(CARD);

        expect(mail.text()).not.toContain('To ');
        expect(mail.find('[data-testid="insight-mail-mailable"]').exists()).toBe(false);
    });

    it('tells how many messages were not shown', () => {
        const wrapper = render([makeMail()], 4);

        expect(wrapper.find('[data-testid="insight-dropped"]').text()).toContain('4');
    });
});
