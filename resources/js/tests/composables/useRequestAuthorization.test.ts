import { useRequestAuthorization } from '@/composables/request/useRequestAuthorization';
import { AuthorizationType } from '@/interfaces/generated';
import { createPinia, setActivePinia } from 'pinia';
import { useRequestStore } from '@/stores';
import { createTestingPinia } from '@pinia/testing';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive, nextTick } from 'vue';

/*
 * Fixtures.
 */

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
        it('initializes with default authorization if not set in store', () => {
            // @ts-expect-error Attempt to assign to const or readonly variable
            requestStore.pendingRequestData = null;

            const { authorization } = useRequestAuthorization();

            expect(authorization.value.type).toBe(AuthorizationType.CurrentUser);
        });

        it('switches between authorization types and restores cached state', async () => {
            // Arrange

            const {
                authorization,
                updateAuthorizationType,
                updateCurrentAuthorizationValue,
            } = useRequestAuthorization();

            // Act


            // Switch to Bearer
            updateAuthorizationType(AuthorizationType.Bearer);
            await nextTick();
            updateCurrentAuthorizationValue('token');
            await nextTick();

            // Assert

            expect(authorization.value).toEqual({
                type: AuthorizationType.Bearer,
                value: 'token',
            });

            // Act


            // Switch to Basic
            updateAuthorizationType(AuthorizationType.Basic);
            await nextTick();

            // Assert

            expect(authorization.value.type).toBe(AuthorizationType.Basic);

            // Act

            // Switch back to Bearer - should restore 'token'
            updateAuthorizationType(AuthorizationType.Bearer);
            await nextTick();

            // Assert

            expect(authorization.value).toEqual({
                type: AuthorizationType.Bearer,
                value: 'token',
            });
        });

        it('persists authorization back to the request store via actions', async () => {
            // Arrange

            const { updateAuthorizationType, updateCurrentAuthorizationValue } =
                useRequestAuthorization();

            const spy = vi.spyOn(requestStore, 'updateAuthorization');

            // Act

            // Switch to Bearer
            updateAuthorizationType(AuthorizationType.Bearer);
            await nextTick();

            // Set value
            updateCurrentAuthorizationValue('token');
            await nextTick();

            // Assert

            expect(spy).toHaveBeenCalledWith(
                expect.objectContaining({
                    type: AuthorizationType.Bearer,
                    value: 'token',
                }),
            );
        });
    });
});
