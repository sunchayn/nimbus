import { RoutesProcessingStrategy } from '@/interfaces/generated';
import { defineStore } from 'pinia';

export interface CurrentUser {
    id: string | number;
}

export type GlobalHeadersArray = Array<{
    header: string;
    type: 'raw' | 'generator';
    value: string | number;
}>;

// TODO [Refactor] convert this to a plain module.
export const useConfigStore = defineStore('config', () => {
    const appName = (window.Nimbus?.appName as string) || 'Nimbus';
    const urlBase = (window.Nimbus?.apiBaseUrl as string) || 'http://localhost';
    const isVersioned = (window.Nimbus?.isVersioned as boolean) || false;
    const basePath = (window.Nimbus?.basePath as string) || '';
    const globalHeaders: GlobalHeadersArray = window.Nimbus?.headers
        ? JSON.parse(window.Nimbus.headers as string)
        : [];
    const currentUser = window.Nimbus?.currentUser
        ? JSON.parse(window.Nimbus.currentUser as string)
        : null;
    const applications: Record<string, string> = window.Nimbus?.applications
        ? JSON.parse(window.Nimbus.applications as string)
        : {};
    const activeApplication = window.Nimbus?.activeApplication || null;
    const primaryProcessorName =
        (window.Nimbus?.primaryProcessorName as RoutesProcessingStrategy) ||
        RoutesProcessingStrategy.AutoDetect;
    const showOperationId = (window.Nimbus?.showOperationId as boolean) || false;

    // Derived values
    const isLoggedIn = currentUser !== null;
    const userId = currentUser?.id ?? null;

    return {
        appName,
        apiUrl: urlBase,
        appBasePath: basePath,
        headers: globalHeaders,
        isVersioned,
        isLoggedIn,
        userId,
        applications,
        activeApplication,
        primaryProcessorName,
        showOperationId,
    };
});
