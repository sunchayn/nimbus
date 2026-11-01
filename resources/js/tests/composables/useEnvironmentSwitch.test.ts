import { useEnvironmentSwitch } from '@/composables/environment/useEnvironmentSwitch';
import { AuthorizationType } from '@/interfaces/generated';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const warning = vi.fn();
const tabs = [
    { request: { authorization: { type: AuthorizationType.Impersonate, value: 1 } } },
    { request: { authorization: { type: AuthorizationType.Bearer, value: 'x' } } },
];

vi.mock('@/components/base/toast', () => ({
    useAppToast: () => ({ warning }),
}));

vi.mock('@/stores', async importOriginal => {
    const actual = await importOriginal<object>();
    const state = { isCurrentHost: true };

    return {
        ...actual,
        useTabsStore: () => ({ tabs }),
        useEnvironmentVariablesStore: () => ({
            select: (id: string) => (state.isCurrentHost = id === 'current'),
            get isCurrentHost() {
                return state.isCurrentHost;
            },
            activeEnvironment: { name: 'Staging' },
        }),
    };
});

describe('useEnvironmentSwitch', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        warning.mockClear();
    });

    it('keeps tabs untouched on the current host', () => {
        useEnvironmentSwitch()('current');

        expect(tabs[0].request.authorization.type).toBe(AuthorizationType.Impersonate);
        expect(warning).not.toHaveBeenCalled();
    });

    it('resets host only authorization and warns when leaving the current host', () => {
        useEnvironmentSwitch()('staging');

        expect(tabs[0].request.authorization.type).toBe(AuthorizationType.None);
        expect(tabs[1].request.authorization.type).toBe(AuthorizationType.Bearer);
        expect(warning).toHaveBeenCalledTimes(1);
    });
});
