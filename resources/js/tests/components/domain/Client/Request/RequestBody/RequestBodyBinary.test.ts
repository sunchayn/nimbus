import RequestBodyBinary from '@/components/domain/Client/Request/RequestBody/RequestBodyBinary.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const pickFile = async (wrapper: ReturnType<typeof mount>, file: File) => {
    const input = wrapper.find('[data-testid="binary-file-input"]');

    Object.defineProperty(input.element, 'files', { value: [file] });
    await input.trigger('change');

    return wrapper.emitted('update:modelValue')?.[0]?.[0] as FormData;
};

describe('RequestBodyBinary', () => {
    it('stores the picked file as a single entry', async () => {
        const wrapper = mount(RequestBodyBinary, { props: { modelValue: null } });
        const file = new File(['abc'], 'a.png', { type: 'image/png' });

        const emitted = await pickFile(wrapper, file);

        expect(emitted.get('file')).toBe(file);
    });

    it('shows the name of the picked file', async () => {
        const formData = new FormData();
        formData.append('file', new File(['abc'], 'a.png', { type: 'image/png' }));

        const wrapper = mount(RequestBodyBinary, { props: { modelValue: formData } });

        expect(wrapper.find('[data-testid="binary-file-name"]').text()).toContain(
            'a.png',
        );
    });

    it('clears the picked file', async () => {
        const formData = new FormData();
        formData.append('file', new File(['abc'], 'a.png', { type: 'image/png' }));

        const wrapper = mount(RequestBodyBinary, { props: { modelValue: formData } });

        await wrapper.find('button').trigger('click');

        expect(wrapper.emitted('update:modelValue')?.[0]?.[0]).toBeNull();
    });
});
