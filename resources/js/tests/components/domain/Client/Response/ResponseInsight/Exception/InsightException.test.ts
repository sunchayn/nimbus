import InsightException from '@/components/domain/Client/Response/ResponseInsight/Exception/InsightException.vue';
import type { InsightExceptionInfo } from '@/interfaces/http';
import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { makeException } from '../helpers';

const render = (exception: InsightExceptionInfo) =>
    mount(InsightException, { props: { exception } });

const frame = {
    file: '/app/Foo.php',
    line: 12,
    call: 'Foo::bar',
    code: { 12: 'throw new Exception();' },
};

describe('InsightException', () => {
    it('shows the class, the message, and the location', () => {
        const wrapper = render(makeException());

        expect(wrapper.text()).toContain('RuntimeException');
        expect(wrapper.find('[data-testid="insight-exception-message"]').text()).toBe(
            'Boom',
        );
        expect(wrapper.text()).toContain('/app/Foo.php:10');
    });

    it('has no link to an editor', () => {
        expect(render(makeException()).find('a').exists()).toBe(false);
    });

    it('has no stack trace card when the trace is empty', () => {
        expect(render(makeException()).text()).not.toContain('Stack trace');
    });

    it('keeps the stack frames closed and counts them', async () => {
        const wrapper = render(makeException({ trace: [frame, frame] }));

        expect(wrapper.text()).toContain('Stack trace');
        expect(wrapper.text()).toContain('2');
        expect(wrapper.findAll('[data-testid="insight-frame"]')).toHaveLength(0);

        await wrapper.find('[data-testid="insight-card-trigger"]').trigger('click');
        await flushPromises();

        expect(wrapper.findAll('[data-testid="insight-frame"]')).toHaveLength(2);
    });

    it('shows the call, the location, and the code of a frame', async () => {
        const wrapper = render(makeException({ trace: [frame] }));

        await wrapper.find('[data-testid="insight-card-trigger"]').trigger('click');
        await flushPromises();

        const shown = wrapper.find('[data-testid="insight-frame"]');

        expect(shown.text()).toContain('Foo::bar');
        expect(shown.text()).toContain('/app/Foo.php:12');
        expect(shown.find('pre').text()).toBe('12  throw new Exception();');
    });

    it('shows no code block for a frame without code', async () => {
        const wrapper = render(makeException({ trace: [{ ...frame, code: {} }] }));

        await wrapper.find('[data-testid="insight-card-trigger"]').trigger('click');
        await flushPromises();

        expect(wrapper.find('[data-testid="insight-frame"] pre').exists()).toBe(false);
    });
});
