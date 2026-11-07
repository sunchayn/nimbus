import ResponsePlainView from '@/components/domain/Client/Response/ResponsePlainView/ResponsePlainView.vue';
import { createMockResponse } from '@/tests/_utils/testFactories';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('ResponsePlainView', () => {
    it('shows the timing and size line and the raw text', () => {
        const wrapper = mount(ResponsePlainView, {
            props: {
                response: createMockResponse({ sizeInBytes: 2048, body: 'hello' }),
                durationInMs: 42,
            },
        });

        expect(wrapper.find('[data-testid="response-plain-view-summary"]').text()).toBe(
            '42 ms, 2.05kB',
        );
        expect(wrapper.find('[data-testid="response-plain-view-text"]').text()).toContain(
            'HTTP 200 OK',
        );
    });
});
