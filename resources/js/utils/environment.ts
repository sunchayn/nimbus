import { AuthorizationType } from '@/interfaces/generated';

/**
 * Accepts only absolute http and https URLs.
 */
export const isValidBaseUrl = (value: string): boolean => {
    try {
        const url = new URL(value.trim());

        return url.protocol === 'http:' || url.protocol === 'https:';
    } catch {
        return false;
    }
};

export const normalizeBaseUrl = (value: string): string =>
    value.trim().replace(/\/+$/, '');

export const isProductionEnvironment = (environment: {
    id: string;
    name: string;
    detected?: string | null;
}): boolean => {
    if (environment.detected) {
        return environment.detected === 'production';
    }

    return /^prod(uction)?$/i.test(environment.id) || /^prod/i.test(environment.name);
};

/**
 * Authorization types that need the current host application, so they are off elsewhere.
 */
export const HOST_ONLY_AUTHORIZATION_TYPES: readonly AuthorizationType[] = [
    AuthorizationType.CurrentUser,
    AuthorizationType.Impersonate,
    AuthorizationType.Token,
];
