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
        // Arrange

        const formData = buildFormData({ name: 'Jane' });

        // Act

        const { wrapper } = mountHost(formData);
        await flush();

        // Assert

        const keys = wrapper
            .findAll('input[name="kv-key"]')
            .map(input => (input.element as HTMLInputElement).value);

        expect(keys).toContain('name');
    });

    it('keeps the form data after being unmounted and mounted again', async () => {
        // Arrange

        const { wrapper, model, visible } = mountHost(
            buildFormData({ name: 'Jane', age: '30' }),
        );
        await flush();

        // Act

        visible.value = false;
        await flush();
        visible.value = true;
        await flush();

        // Assert

        const entries = Object.fromEntries((model.value as FormData).entries());

        expect(entries).toEqual({ name: 'Jane', age: '30' });
        expect(
            wrapper
                .findAll('input[name="kv-key"]')
                .map(input => (input.element as HTMLInputElement).value),
        ).toEqual(expect.arrayContaining(['name', 'age']));
    });

    it('keeps a disabled row disabled after being unmounted and mounted again', async () => {
        // Arrange

        const { wrapper, visible } = mountHost(buildFormData({ name: 'Jane' }));
        await flush();

        // Act

        await wrapper.find('button[role="switch"]').trigger('click');
        await flush();

        visible.value = false;
        await flush();
        visible.value = true;
        await flush();

        // Assert

        expect(wrapper.find('button[role="switch"]').attributes('aria-checked')).toBe(
            'false',
        );
    });

    it('puts a picked file in the form data', async () => {
        // Arrange

        const { wrapper, model } = mountHost(null);
        await flush();

        const file = new File(['content'], 'me.png');

        // Act

        wrapper
            .findComponent({ name: 'KeyValueParameters' })
            .vm.$emit('update:parameters', [
                {
                    id: 1,
                    type: 'file',
                    key: 'avatar',
                    value: file.name,
                    enabled: true,
                    file,
                },
            ]);
        await flush();

        // Assert

        expect((model.value as FormData).get('avatar')).toBe(file);
    });

    it('keeps an unfilled file row as an empty file so the row stays a file field', async () => {
        // Arrange

        const { wrapper, model } = mountHost(null);
        await flush();

        // Act

        wrapper
            .findComponent({ name: 'KeyValueParameters' })
            .vm.$emit('update:parameters', [
                {
                    id: 1,
                    type: 'file',
                    key: 'avatar',
                    value: '',
                    enabled: true,
                },
            ]);
        await flush();

        // Assert

        const entry = (model.value as FormData).get('avatar') as File;

        expect(entry).toBeInstanceOf(File);
        expect(entry.size).toBe(0);
        expect(entry.name).toBe('');
    });

    it('keeps the name of a file row as an empty file until the file is picked again', async () => {
        // Arrange

        const { wrapper, model } = mountHost(null);
        await flush();

        // Act

        wrapper
            .findComponent({ name: 'KeyValueParameters' })
            .vm.$emit('update:parameters', [
                {
                    id: 1,
                    type: 'file',
                    key: 'avatar',
                    value: 'me.png',
                    enabled: true,
                },
            ]);
        await flush();

        // Assert

        const entry = (model.value as FormData).get('avatar') as File;

        expect(entry.name).toBe('me.png');
        expect(entry.size).toBe(0);
    });

    it('keeps two rows that share an array key', async () => {
        // Arrange

        const { wrapper, model } = mountHost(null);
        await flush();

        const first = new File(['a'], 'a.png');
        const second = new File(['b'], 'b.png');

        // Act

        wrapper
            .findComponent({ name: 'KeyValueParameters' })
            .vm.$emit('update:parameters', [
                {
                    id: 1,
                    type: 'file',
                    key: 'photos[]',
                    value: 'a.png',
                    enabled: true,
                    file: first,
                },
                {
                    id: 2,
                    type: 'file',
                    key: 'photos[]',
                    value: 'b.png',
                    enabled: true,
                    file: second,
                },
            ]);
        await flush();

        // Assert

        expect((model.value as FormData).getAll('photos[]')).toEqual([first, second]);
    });

    it('leaves a disabled row out of the form data', async () => {
        // Arrange

        const { wrapper, model } = mountHost(null);
        await flush();

        // Act

        wrapper
            .findComponent({ name: 'KeyValueParameters' })
            .vm.$emit('update:parameters', [
                {
                    id: 1,
                    type: 'text',
                    key: 'name',
                    value: 'Jane',
                    enabled: false,
                },
            ]);
        await flush();

        // Assert

        expect((model.value as FormData).has('name')).toBe(false);
    });
});
