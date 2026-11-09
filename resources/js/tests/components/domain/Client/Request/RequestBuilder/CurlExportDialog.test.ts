import CurlExportDialog from '@/components/domain/Client/Request/RequestBuilder/CurlExportDialog.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const mountDialog = (props = {}) =>
    mount(CurlExportDialog, {
        props: {
            open: true,
            command: 'curl https://a.test',
            hasSpecialAuth: false,
            ...props,
        },
        global: {
            stubs: {
                AppDialog: { template: '<div><slot name="body" /></div>' },
                AppAlert: { template: '<div data-testid="auth-alert" />' },
                AppButton: true,
            },
        },
    });

describe('CurlExportDialog', () => {
    it('shows the cURL command', () => {
        const wrapper = mountDialog();

        expect(wrapper.find('pre').text()).toBe('curl https://a.test');
        expect(wrapper.find('[data-testid="auth-alert"]').exists()).toBe(false);
    });

    it('warns when the authorization cannot be exported', () => {
        const wrapper = mountDialog({ hasSpecialAuth: true });

        expect(wrapper.find('[data-testid="auth-alert"]').exists()).toBe(true);
    });
});
