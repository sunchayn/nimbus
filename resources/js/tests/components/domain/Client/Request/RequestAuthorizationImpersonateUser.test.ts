import RequestAuthorizationImpersonateUser from '@/components/domain/Client/Request/RequestAuthorization/RequestAuthorizationImpersonateUser.vue';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const respondWith = (guards: string[]) =>
    vi.fn().mockResolvedValue({
        ok: true,
        json: async () => ({
            guard: 'web',
            guards,
            actors: [{ id: '01HZ', label: 'Ada', model: 'App\\Models\\User' }],
        }),
    });

describe('RequestAuthorizationImpersonateUser', () => {
    beforeEach(() => {
        vi.stubGlobal('fetch', respondWith(['web', 'api']));
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('shows a non numeric key as written', async () => {
        const wrapper = mount(RequestAuthorizationImpersonateUser, {
            props: { modelValue: '01HZX3Y8K2M4N6P8Q0R2S4T6V8' },
        });

        await flushPromises();

        expect((wrapper.find('#user-id').element as HTMLInputElement).value).toBe(
            '01HZX3Y8K2M4N6P8Q0R2S4T6V8',
        );
    });

    it('emits the typed key as a string', async () => {
        const wrapper = mount(RequestAuthorizationImpersonateUser, {
            props: { modelValue: '' },
        });

        await wrapper.find('#user-id').setValue('abc-123');

        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['abc-123']);
    });

    it('loads the users of the requested guard', async () => {
        mount(RequestAuthorizationImpersonateUser, {
            props: { modelValue: '', guard: 'api' },
        });

        await flushPromises();

        expect(vi.mocked(fetch).mock.calls[0][0]).toContain('/api/actors?guard=api');
    });

    it('hides the guard select when there is one guard', async () => {
        vi.stubGlobal('fetch', respondWith(['web']));

        const wrapper = mount(RequestAuthorizationImpersonateUser, {
            props: { modelValue: '' },
        });

        await flushPromises();

        expect(wrapper.find('[data-testid="actor-guard-select"]').exists()).toBe(false);
    });

    it('fills the key with the user picked from the list', async () => {
        const wrapper = mount(RequestAuthorizationImpersonateUser, {
            props: { modelValue: '' },
            attachTo: document.body,
        });

        await flushPromises();
        await wrapper.find('[data-testid="actor-picker-button"]').trigger('click');
        await flushPromises();

        const option = document.querySelector<HTMLElement>(
            '[data-testid="actor-option"]',
        );
        option?.click();
        await flushPromises();

        expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual(['01HZ']);

        wrapper.unmount();
    });
});
