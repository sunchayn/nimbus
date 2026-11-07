import type { RouteDefinition } from '@/interfaces/routes/routes';

/**
 * Turns a throttle value such as `60,1` into a short sentence.
 * A name without a comma is a rate limiter that the app defines.
 */
export function describeThrottle(throttle: string | undefined): string | null {
    if (!throttle) {
        return null;
    }

    const [attempts, minutes = '1'] = throttle.split(',');

    if (!/^\d+$/.test(attempts)) {
        return `Rate limited by "${throttle}"`;
    }

    return minutes === '1'
        ? `${attempts} requests per minute`
        : `${attempts} requests per ${minutes} minutes`;
}

/**
 * Describes what a route parameter accepts, from its model binding and its pattern.
 */
export function describeRouteParameter(
    route: RouteDefinition | undefined,
    parameter: string,
): string | null {
    const binding = route?.metadata?.bindings?.find(
        candidate => candidate.parameter === parameter,
    );
    const pattern = route?.metadata?.where?.[parameter];

    const parts: string[] = [];

    if (binding) {
        const model = binding.model.split('\\').pop();

        parts.push(binding.key ? `${model} by ${binding.key}` : `${model} id`);
    }

    if (pattern) {
        parts.push(`matches ${pattern}`);
    }

    return parts.length ? parts.join(', ') : null;
}

/**
 * Whether the route uses the signed middleware, so it only accepts a signed URL.
 */
export function isSignedRoute(route: RouteDefinition | undefined): boolean {
    return (
        route?.metadata?.middleware?.some(entry =>
            entry.startsWith('Illuminate\\Routing\\Middleware\\ValidateSignature'),
        ) ?? false
    );
}
