import ToolboxDialog from '@/components/domain/Toolbox/ToolboxDialog.vue';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const stubs = {
    CryptTool: { template: '<div data-testid="crypt-tool" />' },
    SignedUrlTool: { template: '<div data-testid="signed-url-tool" />' },
    HashTool: { template: '<div data-testid="hash-tool" />' },
    TokenTool: { template: '<div data-testid="token-tool" />' },
    SessionTool: { template: '<div data-testid="session-tool" />' },
};

const query = (selector: string) => document.querySelector<HTMLElement>(selector);

describe('ToolboxDialog', () => {
    beforeEach(() => {
        Element.prototype.scrollTo = vi.fn();
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('lists every tool as a tab and shows the first one', async () => {
        const wrapper = mount(ToolboxDialog, {
            props: { open: true },
            global: { stubs },
            attachTo: document.body,
        });

        await flushPromises();

        expect(document.querySelectorAll('[role="tab"]')).toHaveLength(5);
        expect(query('[data-testid="crypt-tool"]')?.style.display).not.toBe('none');
        expect(query('[data-testid="hash-tool"]')?.style.display).toBe('none');

        wrapper.unmount();
    });

    it('shows the tool of the selected tab', async () => {
        const wrapper = mount(ToolboxDialog, {
            props: { open: true },
            global: { stubs },
            attachTo: document.body,
        });

        await flushPromises();

        const hashTab = Array.from(
            document.querySelectorAll<HTMLElement>('[role="tab"]'),
        ).find(tab => tab.textContent?.includes('Hash'));

        hashTab?.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
        hashTab?.click();
        await flushPromises();

        expect(query('[data-testid="hash-tool"]')?.style.display).not.toBe('none');
        expect(query('[data-testid="crypt-tool"]')?.style.display).toBe('none');

        wrapper.unmount();
    });

    it('keeps the tools mounted after the dialog closes', async () => {
        const wrapper = mount(ToolboxDialog, {
            props: { open: true },
            global: { stubs },
            attachTo: document.body,
        });

        await flushPromises();
        await wrapper.setProps({ open: false });
        await flushPromises();

        expect(query('[data-testid="crypt-tool"]')).not.toBeNull();

        wrapper.unmount();
    });
});
