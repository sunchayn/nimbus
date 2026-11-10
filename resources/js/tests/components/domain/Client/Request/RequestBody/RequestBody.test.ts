import RequestBody from '@/components/domain/Client/Request/RequestBody/RequestBody.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

/*
 * Fixtures.
 */

vi.mock('@/composables/request/useRequestBody', () => ({
    useRequestBody: () => ({
        payloadType: ref('json'),
        payload: ref('{}'),
        pendingRequestData: ref(null),
        supportsAutoFill: ref(true),
        autofill: vi.fn(),
        types: [],
    }),
}));

vi.mock('@/stores', () => ({
    useRequestStore: () => ({ pendingRequestData: null }),
    useRoutesStore: () => ({
        isMissingImplementation: () => false,
        isUndocumented: () => false,
    }),
}));

const stub = (testId: string) => ({ template: `<button data-testid="${testId}" />` });

const createWrapper = () =>
    mount(RequestBody, {
        global: {
            stubs: {
                RequestBodySelector: true,
                RequestBodyContent: true,
                ImplementationMissingWarning: true,
                RouteAbsentInPrimarySourceInfo: true,
                RouteExtractionError: true,
                RequestBodyRecordPicker: stub('record-picker-trigger'),
                RequestBodyAutoFillButton: stub('request-body-autofill'),
            },
        },
    });

describe('RequestBody', () => {
    it('groups the record picker before the autofill button', () => {
        // Act

        const wrapper = createWrapper();

        // Assert

        const toolbox = wrapper.get('[data-testid="request-body-toolbox"]');
        const order = toolbox
            .findAll('button')
            .map(button => button.attributes('data-testid'));

        expect(order).toEqual(['record-picker-trigger', 'request-body-autofill']);
        expect(toolbox.classes()).toContain('flex');
        expect(toolbox.classes()).toContain('gap-tight');
    });
});
