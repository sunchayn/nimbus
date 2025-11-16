import { useRequestAuthorization } from '@/composables/request/useRequestAuthorization';
import { AuthorizationType } from '@/interfaces/generated';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive } from 'vue';

const pendingRequestData = reactive({
    authorization: { type: AuthorizationType.None, value: null },
});

const updateAuthorization = vi.fn();

vi.mock('@/stores', async importOriginal => {
    const actual = await importOriginal<object>();

    return {
        ...actual,
        useRequestStore: () => ({
            pendingRequestData,
            updateAuthorization,
        }),
    };
});

describe('useRequestAuthorization', () => {
    beforeEach(() => {
        pendingRequestData.authorization = { type: AuthorizationType.None, value: null };
        updateAuthorization.mockClear();
    });

    it('initializes with current request authorization', () => {
        setActivePinia(createPinia());

        const { authorization } = useRequestAuthorization();

        expect(authorization.value.type).toBe(AuthorizationType.None);
    });

    it('switches between authorization types and restores cached state', () => {
        setActivePinia(createPinia());

        const {
            authorization,
            updateAuthorizationType,
            updateCurrentAuthorizationValue,
        } = useRequestAuthorization();

        updateAuthorizationType(AuthorizationType.Bearer);
        updateCurrentAuthorizationValue('token');
        expect(authorization.value).toEqual({
            type: AuthorizationType.Bearer,
            value: 'token',
        });

        updateAuthorizationType(AuthorizationType.Basic);
        expect(authorization.value.type).toBe(AuthorizationType.Basic);

        updateAuthorizationType(AuthorizationType.Bearer);
        expect(authorization.value).toEqual({
            type: AuthorizationType.Bearer,
            value: 'token',
        });
    });

    it('persists authorization back to the request store', () => {
        setActivePinia(createPinia());

        updateAuthorization.mockClear();

        const {
            saveAuthorizationToStore,
            updateAuthorizationType,
            updateCurrentAuthorizationValue,
        } = useRequestAuthorization();

        updateAuthorizationType(AuthorizationType.Bearer);
        updateCurrentAuthorizationValue('token');

        saveAuthorizationToStore();

        expect(updateAuthorization).toHaveBeenCalledWith({
            type: AuthorizationType.Bearer,
            value: 'token',
        });
    });
});
