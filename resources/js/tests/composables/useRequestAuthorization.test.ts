import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive } from 'vue';
import { useRequestAuthorization } from '@/composables/request/useRequestAuthorization';
import { AuthorizationType } from '@/interfaces/generated';

/*
 * Fixtures.
 */

const pendingRequestData = reactive({
    authorization: { type: AuthorizationType.None, value: null as any },
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
        setActivePinia(createPinia());
        pendingRequestData.authorization = { type: AuthorizationType.None, value: null };
        vi.clearAllMocks();
    });

    /*
     * Initialization tests.
     */

    describe('Initialization', () => {
        it('initializes with current request authorization', () => {
            // Act

            const { authorization } = useRequestAuthorization();

            // Assert

            expect(authorization.value.type).toBe(AuthorizationType.None);
        });
    });

    /*
     * State Transition tests.
     */

    describe('Behavior', () => {
        it('switches between authorization types and restores cached state', () => {
            // Arrange

            const {
                authorization,
                updateAuthorizationType,
                updateCurrentAuthorizationValue,
            } = useRequestAuthorization();

            // Act

            updateAuthorizationType(AuthorizationType.Bearer);
            updateCurrentAuthorizationValue('token');

            // Assert

            expect(authorization.value).toEqual({
                type: AuthorizationType.Bearer,
                value: 'token',
            });

            // Act

            updateAuthorizationType(AuthorizationType.Basic);
            expect(authorization.value.type).toBe(AuthorizationType.Basic);

            updateAuthorizationType(AuthorizationType.Bearer);

            // Assert

            expect(authorization.value).toEqual({
                type: AuthorizationType.Bearer,
                value: 'token',
            });
        });

        it('persists authorization back to the request store', () => {
            // Arrange

            const {
                saveAuthorizationToStore,
                updateAuthorizationType,
                updateCurrentAuthorizationValue,
            } = useRequestAuthorization();

            updateAuthorizationType(AuthorizationType.Bearer);
            updateCurrentAuthorizationValue('token');

            // Act

            saveAuthorizationToStore();

            // Assert

            expect(updateAuthorization).toHaveBeenCalledWith({
                type: AuthorizationType.Bearer,
                value: 'token',
            });
        });
    });
});
