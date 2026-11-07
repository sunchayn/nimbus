import RequestAuthorizationTokenForUser from '@/components/domain/Client/Request/RequestAuthorization/RequestAuthorizationTokenForUser.vue';
import { clearActorsCache } from '@/composables/request/useActors';
import { flushPromises, mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

describe('RequestAuthorizationTokenForUser', () => {
    const useTokenGuards = (guards: string[]) => {
        window.Nimbus = { tokenGuards: JSON.stringify(guards) } as typeof window.Nimbus;
        setActivePinia(createPinia());
    };

    beforeEach(() => {
        clearActorsCache();
        useTokenGuards(['web', 'api']);
        vi.stubGlobal(
            'fetch',
            vi.fn().mockResolvedValue({
                ok: true,
                json: async () => ({ guard: 'web', guards: ['web', 'api'], actors: [] }),
            }),
        );
    });

    afterEach(() => {
        window.Nimbus = undefined as unknown as typeof window.Nimbus;
        vi.unstubAllGlobals();
    });

    it('shows the user key and the abilities', () => {
        const wrapper = mount(RequestAuthorizationTokenForUser, {
            props: { modelValue: '01HZ', abilities: 'posts:read' },
        });

        expect((wrapper.find('#user-id').element as HTMLInputElement).value).toBe('01HZ');
        expect((wrapper.find('#token-abilities').element as HTMLInputElement).value).toBe(
            'posts:read',
        );
    });

    it('emits the typed abilities', async () => {
        const wrapper = mount(RequestAuthorizationTokenForUser, {
            props: { modelValue: '1', abilities: '*' },
        });

        await wrapper.find('#token-abilities').setValue('posts:write');

        expect(wrapper.emitted('update:abilities')?.[0]).toEqual(['posts:write']);
    });

    it('forwards a guard change', async () => {
        useTokenGuards(['api']);

        const wrapper = mount(RequestAuthorizationTokenForUser, {
            props: { modelValue: '1', abilities: '*', guard: 'web' },
        });

        await flushPromises();

        expect(wrapper.emitted('update:guard')?.[0]).toEqual(['api']);
    });

    it('hides the form and explains when no guard can create tokens', () => {
        useTokenGuards([]);

        const wrapper = mount(RequestAuthorizationTokenForUser, {
            props: { modelValue: '1', abilities: '*' },
        });

        expect(wrapper.find('[data-testid="no-token-guard"]').text()).toContain(
            'HasApiTokens',
        );
        expect(wrapper.find('#token-abilities').exists()).toBe(false);
    });
});
