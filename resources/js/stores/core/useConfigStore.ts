import type { EnvironmentSource } from '@/interfaces/generated';
import { RoutesProcessingStrategy } from '@/interfaces/generated';
import { defineStore } from 'pinia';

export interface CurrentUser {
    id: string | number;
}

export interface ServerEnvironment {
    id: string;
    name: string;
    baseUrl: string;
    enabled: boolean;
    source: EnvironmentSource;
    detected: string | null;
}

export type GlobalHeadersArray = Array<{
    header: string;
    type: 'raw' | 'generator';
    value: string | number;
}>;

// TODO [Refactor] convert this to a plain module.
export const useConfigStore = defineStore('config', () => {
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
    const guards: string[] = window.Nimbus?.guards
        ? JSON.parse(window.Nimbus.guards as string)
        : [];
    const defaultGuard = window.Nimbus?.defaultGuard || null;
    const tokenGuards: string[] = window.Nimbus?.tokenGuards
        ? JSON.parse(window.Nimbus.tokenGuards as string)
        : [];
    const isRateLimiterEphemeral = window.Nimbus?.ephemeralRateLimiter === true;
    const activeApplication = window.Nimbus?.activeApplication || null;
    const primaryProcessorName =
        (window.Nimbus?.primaryProcessorName as RoutesProcessingStrategy) ||
        RoutesProcessingStrategy.AutoDetect;
    const showOperationId = (window.Nimbus?.showOperationId as boolean) || false;

    const environments: ServerEnvironment[] = window.Nimbus?.environments
        ? JSON.parse(window.Nimbus.environments as string)
        : [];

    // Derived values
    const isLoggedIn = currentUser !== null;
    const userId = currentUser?.id ?? null;

    return {
        apiUrl: urlBase,
        environments,
        appBasePath: basePath,
        headers: globalHeaders,
        isVersioned,
        isLoggedIn,
        userId,
        applications,
        isRateLimiterEphemeral,
        guards,
        defaultGuard,
        tokenGuards,
        activeApplication,
        primaryProcessorName,
        showOperationId,
    };
});
