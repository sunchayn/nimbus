import { useRequestAuthorization } from '@/composables/request/useRequestAuthorization';
import { AuthorizationType } from '@/interfaces/generated';
import { useRequestStore } from '@/stores';
import { createTestingPinia } from '@pinia/testing';
import { setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

describe('useRequestAuthorization', () => {
    let requestStore: ReturnType<typeof useRequestStore>;

    beforeEach(() => {
        setActivePinia(
            createTestingPinia({
                createSpy: vi.fn,
                stubActions: false,
                initialState: {
                    _requestBuilder: {
                        pendingRequestData: {
                            authorization: { type: AuthorizationType.None, value: null },
                        },
                    },
                },
            }),
        );

        requestStore = useRequestStore();
    });

    it('initializes with current request authorization', () => {
        const { authorization } = useRequestAuthorization();

        expect(authorization.value.type).toBe(AuthorizationType.None);
    });

    it('initializes with default authorization if not set in store', () => {
        // @ts-ignore
        requestStore.pendingRequestData = null;

        const { authorization } = useRequestAuthorization();

        expect(authorization.value.type).toBe(AuthorizationType.CurrentUser);
    });

    it('switches between authorization types and restores cached state', async () => {
        const {
            authorization,
            updateAuthorizationType,
            updateCurrentAuthorizationValue,
        } = useRequestAuthorization();

        // Switch to Bearer
        updateAuthorizationType(AuthorizationType.Bearer);
        await nextTick();

        // Set value
        updateCurrentAuthorizationValue('token');
        await nextTick();

        expect(authorization.value).toEqual({
            type: AuthorizationType.Bearer,
            value: 'token',
        });

        // Switch to Basic
        updateAuthorizationType(AuthorizationType.Basic);
        await nextTick();

        expect(authorization.value.type).toBe(AuthorizationType.Basic);
        expect(authorization.value.value).toEqual({ username: '', password: '' });

        // Switch back to Bearer - should restore 'token'
        updateAuthorizationType(AuthorizationType.Bearer);
        await nextTick();

        expect(authorization.value).toEqual({
            type: AuthorizationType.Bearer,
            value: 'token',
        });
    });

    it('persists authorization back to the request store via actions', async () => {
        const { updateAuthorizationType, updateCurrentAuthorizationValue } =
            useRequestAuthorization();

        const spy = vi.spyOn(requestStore, 'updateAuthorization');

        // Switch to Bearer
        updateAuthorizationType(AuthorizationType.Bearer);
        await nextTick();

        // Set value
        updateCurrentAuthorizationValue('token');
        await nextTick();

        // Check if it was called with the final expected state
        expect(spy).toHaveBeenCalledWith(expect.objectContaining({
            type: AuthorizationType.Bearer,
            value: 'token',
        }));
    });
});
