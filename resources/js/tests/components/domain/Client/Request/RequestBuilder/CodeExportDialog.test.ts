import CodeExportDialog from '@/components/domain/Client/Request/RequestBuilder/CodeExportDialog.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const mountDialog = () =>
    mount(CodeExportDialog, {
        props: {
            open: true,
            samples: {
                fetch: 'await fetch("https://a.test")',
                guzzle: '$client->request("GET", "https://a.test")',
            },
            languages: [
                { value: 'fetch', label: 'JavaScript (fetch)' },
                { value: 'guzzle', label: 'PHP (Guzzle)' },
            ],
        },
        global: {
            stubs: {
                AppDialog: { template: '<div><slot name="body" /></div>' },
                AppSelect: true,
                AppButton: true,
            },
        },
    });

describe('CodeExportDialog', () => {
    it('shows the sample of the first language by default', () => {
        const wrapper = mountDialog();

        expect(wrapper.find('[data-testid="code-sample"]').text()).toBe(
            'await fetch("https://a.test")',
        );
    });

    it('shows the sample of the selected language', async () => {
        const wrapper = mountDialog();

        wrapper
            .findComponent({ name: 'AppSelect' })
            .vm.$emit('update:modelValue', 'guzzle');
        await wrapper.vm.$nextTick();

        expect(wrapper.find('[data-testid="code-sample"]').text()).toBe(
            '$client->request("GET", "https://a.test")',
        );
    });
});
