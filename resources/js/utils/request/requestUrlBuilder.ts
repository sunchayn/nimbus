import type { ParameterContract } from '@/interfaces';
import type { ResolverFn } from '@/interfaces/common/env-vars';

/**
 * Checks if a query parameter is valid for inclusion in URLs.
 */
export function isValidQueryParameter(parameter: ParameterContract): boolean {
    return parameter.key.trim() !== '';
}

/**
 * Builds complete request URL with query parameters.
 *
 * Constructs the full URL by combining base URL, endpoint, and
 * enabled query parameters for the current request.
 */
export function buildRequestUrl(
    baseUrl: string,
    endpoint: string,
    queryParameters: ParameterContract[],
    resolver?: ResolverFn,
): string {
    const url = new URL(`${baseUrl}/${endpoint}`);

    queryParameters.forEach(parameter => {
        if (!isValidQueryParameter(parameter)) {
            return;
        }

        const value = resolver ? resolver(parameter.value) : parameter.value;

        appendQueryParam(url.searchParams, parameter.key, value ?? '');
    });

    return url.toString();
}

/**
 * Recursively flattens an object into query parameters using bracket notation.
 */
function appendQueryParam(
    searchParams: URLSearchParams,
    key: string,
    value: unknown,
): void {
    if (value === null || value === undefined) {
        return;
    }

    if (Array.isArray(value)) {
        // Append each array element with [] notation
        value.forEach(item => appendQueryParam(searchParams, `${key}[]`, item));
    } else if (typeof value === 'object') {
        // Recursively handle nested objects
        Object.entries(value).forEach(([subKey, subValue]) => {
            appendQueryParam(searchParams, `${key}[${subKey}]`, subValue);
        });
    } else {
        // Primitive value: append directly
        searchParams.append(key, String(value));
    }
}
