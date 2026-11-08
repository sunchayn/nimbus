import { AppButton } from '@/components/base/button';
import RequestBodyRecordPicker from '@/components/domain/Client/Request/RequestBody/RequestBodyRecordPicker.vue';
import { RequestBodyTypeEnum } from '@/interfaces/http';
import { flushPromises, mount } from '@vue/test-utils';
import type { JSONSchema7 } from 'json-schema';
import { afterEach, describe, expect, it, vi } from 'vitest';

/*
 * Fixtures.
 */

const mockLookupValuesStore = {
    valuesFor: vi.fn(() => [3, 8]),
};

vi.mock('@/stores', async importOriginal => ({
    ...(await importOriginal<object>()),
    useLookupValuesStore: () => mockLookupValuesStore,
}));

const schema = {
    type: 'object',
    properties: {
        author_id: { type: 'integer', lookup: { table: 'users', column: 'id' } },
    },
} as JSONSchema7;

const createWrapper = (props = {}) =>
    mount(RequestBodyRecordPicker, {
        props: {
            schema,
            payloadType: RequestBodyTypeEnum.JSON,
            payload: '{}',
            ...props,
        },
        attachTo: document.body,
    });

describe('RequestBodyRecordPicker', () => {
    afterEach(() => {
        document.body.innerHTML = '';
        mockLookupValuesStore.valuesFor.mockReturnValue([3, 8]);
    });

    it('renders an icon only trigger', () => {
        // Act

        const wrapper = createWrapper();

        // Assert

        const trigger = wrapper.get('[data-testid="record-picker-trigger"]');

        expect(trigger.text()).toBe('');
        expect(trigger.attributes('aria-label')).toBe('Existing records');
        wrapper.unmount();
    });

    it('styles the trigger as a neutral outline button', () => {
        // Act

        const wrapper = createWrapper();

        // Assert

        // The request options button is the reference for the outline style.
        const reference = mount(AppButton, {
            props: { variant: 'outline', color: 'neutral', size: 'sm', square: true },
        });
        const trigger = wrapper.get('[data-testid="record-picker-trigger"]');

        expect(trigger.classes()).toEqual(expect.arrayContaining(reference.classes()));
        reference.unmount();
        wrapper.unmount();
    });

    it('opens the popover toward the right of the trigger', async () => {
        // Arrange

        const wrapper = createWrapper();

        // Act

        await wrapper.get('[data-testid="record-picker-trigger"]').trigger('click');
        await flushPromises();

        // Assert

        expect(document.body.querySelector('[data-align="start"]')).not.toBeNull();
        wrapper.unmount();
    });

    it('explains what the records are once opened', async () => {
        // Arrange

        const wrapper = createWrapper();

        // Act

        await wrapper.get('[data-testid="record-picker-trigger"]').trigger('click');
        await flushPromises();

        // Assert

        expect(document.body.textContent).toContain('Existing records');
        expect(document.body.textContent).toContain('already exists in your database');
        wrapper.unmount();
    });

    it('is hidden when no record is available', () => {
        // Arrange

        mockLookupValuesStore.valuesFor.mockReturnValue([]);

        // Act

        const wrapper = createWrapper();

        // Assert

        expect(wrapper.find('[data-testid="record-picker-trigger"]').exists()).toBe(
            false,
        );
        wrapper.unmount();
    });

    it('is hidden for a body that is not JSON', () => {
        // Act

        const wrapper = createWrapper({ payloadType: RequestBodyTypeEnum.FORM_DATA });

        // Assert

        expect(wrapper.find('[data-testid="record-picker-trigger"]').exists()).toBe(
            false,
        );
        wrapper.unmount();
    });

    it('writes the chosen record into the JSON body', async () => {
        // Arrange

        const wrapper = createWrapper();
        await wrapper.get('[data-testid="record-picker-trigger"]').trigger('click');
        await flushPromises();

        const select = document.body.querySelector(
            '[data-testid="record-picker-author_id"]',
        ) as HTMLSelectElement;

        // Act

        select.value = '8';
        select.dispatchEvent(new Event('change', { bubbles: true }));
        await flushPromises();

        // Assert

        const emitted = wrapper.emitted('update:payload')?.[0]?.[0] as string;

        expect(JSON.parse(emitted)).toEqual({ author_id: 8 });
        wrapper.unmount();
    });
});
