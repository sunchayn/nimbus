import RequestAuthorizationImpersonateUser from '@/components/domain/Client/Request/RequestAuthorization/RequestAuthorizationImpersonateUser.vue';
import { clearActorsCache } from '@/composables/request/useActors';
import { flushPromises, mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const respondWith = () =>
    vi.fn().mockResolvedValue({
        ok: true,
        json: async () => ({
            actors: [{ id: '01HZ', label: 'Ada', model: 'App\\Models\\User' }],
        }),
    });

describe('RequestAuthorizationImpersonateUser', () => {
    const useGuards = (guards: string[], tokenGuards: string[] = guards) => {
        window.Nimbus = {
            guards: JSON.stringify(guards),
            defaultGuard: 'web',
            tokenGuards: JSON.stringify(tokenGuards),
        } as typeof window.Nimbus;
        setActivePinia(createPinia());
    };

    beforeEach(() => {
        clearActorsCache();
        useGuards(['web', 'api']);
        vi.stubGlobal('fetch', respondWith());
    });

    afterEach(() => {
        window.Nimbus = undefined as unknown as typeof window.Nimbus;
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
        useGuards(['web']);

        const wrapper = mount(RequestAuthorizationImpersonateUser, {
            props: { modelValue: '' },
        });

        await flushPromises();

        expect(wrapper.find('[data-testid="actor-guard-select"]').exists()).toBe(false);
    });

    it('lists the guards that were preloaded for tokens', async () => {
        useGuards(['web', 'api']);

        const wrapper = mount(RequestAuthorizationImpersonateUser, {
            props: { modelValue: '', tokensOnly: true, guard: 'api' },
        });

        await flushPromises();

        expect(wrapper.find('[data-testid="actor-guard-select"]').exists()).toBe(true);
        expect(vi.mocked(fetch).mock.calls[0][0]).toContain('guard=api');
    });

    it('hides the guard select when one token guard is preloaded', async () => {
        useGuards(['api']);

        const wrapper = mount(RequestAuthorizationImpersonateUser, {
            props: { modelValue: '', tokensOnly: true, guard: 'api' },
        });

        await flushPromises();

        expect(wrapper.find('[data-testid="actor-guard-select"]').exists()).toBe(false);
    });

    it('emits the first token guard when the chosen one is not preloaded', async () => {
        useGuards(['api']);

        const wrapper = mount(RequestAuthorizationImpersonateUser, {
            props: { modelValue: '', tokensOnly: true, guard: 'web' },
        });

        await flushPromises();

        expect(wrapper.emitted('update:guard')?.[0]).toEqual(['api']);
        expect(vi.mocked(fetch).mock.calls[0][0]).toContain('guard=api');
    });

    it('does not load users when no token guard is preloaded', async () => {
        useGuards([]);

        mount(RequestAuthorizationImpersonateUser, {
            props: { modelValue: '', tokensOnly: true },
        });

        await flushPromises();

        expect(fetch).not.toHaveBeenCalled();
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
