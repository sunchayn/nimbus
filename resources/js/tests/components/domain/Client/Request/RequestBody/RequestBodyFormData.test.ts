import RequestBodyFormData from '@/components/domain/Client/Request/RequestBody/RequestBodyFormData.vue';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, h, nextTick, ref } from 'vue';

/*
 * Fixtures.
 */

vi.mock('@/components/base/tooltip', () => ({
    AppTooltip: { name: 'AppTooltip', template: '<slot />' },
}));

const buildFormData = (entries: Record<string, string>): FormData => {
    const formData = new FormData();

    Object.entries(entries).forEach(([key, value]) => formData.set(key, value));

    return formData;
};

// Mimics the body editor, which unmounts the form when another body type is selected.
const mountHost = (initial: FormData | null) => {
    const model = ref<FormData | null>(initial);
    const visible = ref(true);

    const Host = defineComponent({
        setup: () => () =>
            visible.value
                ? h(RequestBodyFormData, {
                      modelValue: model.value,
                      'onUpdate:modelValue': (value: FormData | null) => {
                          model.value = value;
                      },
                  })
                : null,
    });

    return { wrapper: mount(Host), model, visible };
};

const flush = async () => {
    await vi.advanceTimersByTimeAsync(1000);
    await nextTick();
};

describe('RequestBodyFormData', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.useFakeTimers();
    });

    it('shows the rows of the form data it receives', async () => {
        const { wrapper } = mountHost(buildFormData({ name: 'Jane' }));
        await flush();

        const keys = wrapper
            .findAll('input[name="kv-key"]')
            .map(input => (input.element as HTMLInputElement).value);

        expect(keys).toContain('name');
    });

    it('keeps the form data after being unmounted and mounted again', async () => {
        const { wrapper, model, visible } = mountHost(
            buildFormData({ name: 'Jane', age: '30' }),
        );
        await flush();

        visible.value = false;
        await flush();
        visible.value = true;
        await flush();

        const entries = Object.fromEntries((model.value as FormData).entries());

        expect(entries).toEqual({ name: 'Jane', age: '30' });
        expect(
            wrapper
                .findAll('input[name="kv-key"]')
                .map(input => (input.element as HTMLInputElement).value),
        ).toEqual(expect.arrayContaining(['name', 'age']));
    });

    it('keeps a disabled row disabled after being unmounted and mounted again', async () => {
        const { wrapper, visible } = mountHost(buildFormData({ name: 'Jane' }));
        await flush();

        await wrapper.find('button[role="switch"]').trigger('click');
        await flush();

        visible.value = false;
        await flush();
        visible.value = true;
        await flush();

        expect(wrapper.find('button[role="switch"]').attributes('aria-checked')).toBe(
            'false',
        );
    });
});
