import RequestAuthorizationSelector from '@/components/domain/Client/Request/RequestAuthorization/RequestAuthorizationSelector.vue';
import { AuthorizationType } from '@/interfaces/generated';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive } from 'vue';

const environment = reactive({ isCurrentHost: true });

vi.mock('@/stores', async importOriginal => {
    const actual = await importOriginal<object>();

    return {
        ...actual,
        useEnvironmentVariablesStore: () => environment,
        useRequestStore: () => ({ pendingRequestData: null }),
        useSettingsStore: () => ({
            preferences: { defaultAuthorizationType: AuthorizationType.None },
        }),
    };
});

const types = {
    special: [
        { id: AuthorizationType.CurrentUser, label: 'Logged in user' },
        { id: AuthorizationType.Impersonate, label: 'Impersonate' },
    ],
    traditional: [{ id: AuthorizationType.Bearer, label: 'Bearer' }],
};

const itemsOf = (wrapper: ReturnType<typeof mount>) =>
    (
        wrapper.findComponent({ name: 'AppSelect' }).props('items') as Array<
            Array<{ value?: string; disabled?: boolean }>
        >
    )
        .flat()
        .filter(item => item.value);

describe('RequestAuthorizationSelector', () => {
    beforeEach(() => setActivePinia(createPinia()));

    it('enables special types on the current host', () => {
        environment.isCurrentHost = true;
        const items = itemsOf(mount(RequestAuthorizationSelector, { props: { types } }));

        expect(items.some(item => item.disabled)).toBe(false);
    });

    it('disables only host-only types elsewhere', () => {
        environment.isCurrentHost = false;
        const items = itemsOf(mount(RequestAuthorizationSelector, { props: { types } }));

        expect(items.filter(item => item.disabled).map(item => item.value)).toEqual([
            AuthorizationType.CurrentUser,
            AuthorizationType.Impersonate,
        ]);
    });
});
