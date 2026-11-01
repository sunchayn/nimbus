import { useAppToast } from '@/components/base/toast';
import { AuthorizationType } from '@/interfaces/generated';
import { useEnvironmentVariablesStore, useTabsStore } from '@/stores';
import { HOST_ONLY_AUTHORIZATION_TYPES } from '@/utils/environment';

/**
 * Switches the active environment and resets host only authorization on open tabs.
 */
export const useEnvironmentSwitch = (): ((environmentId: string) => void) => {
    const environmentStore = useEnvironmentVariablesStore();
    const tabsStore = useTabsStore();
    const toast = useAppToast();

    return (environmentId: string) => {
        environmentStore.select(environmentId);

        if (environmentStore.isCurrentHost) {
            return;
        }

        const affected = tabsStore.tabs.filter(tab =>
            HOST_ONLY_AUTHORIZATION_TYPES.includes(tab.request.authorization.type),
        );

        affected.forEach(tab => {
            tab.request.authorization = { type: AuthorizationType.None };
        });

        if (affected.length) {
            toast.warning('Authorization reset to "No auth"', {
                description: `${environmentStore.activeEnvironment.name} cannot use the current user or impersonation.`,
            });
        }
    };
};
