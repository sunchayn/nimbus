import type { RouteDefinition } from '@/interfaces/routes/routes';
import {
    describeRouteParameter,
    describeThrottle,
    isSignedRoute,
} from '@/utils/routes/routeMetadata';
import { describe, expect, it } from 'vitest';

const routeWith = (metadata: RouteDefinition['metadata']): RouteDefinition => ({
    endpoint: 'api/products/{product}',
    method: 'GET',
    shortEndpoint: 'products/{product}',
    schema: { shape: {}, extractionErrors: null },
    metadata,
});

describe('routeMetadata', () => {
    describe('describeThrottle', () => {
        it('describes attempts per minute', () => {
            expect(describeThrottle('60,1')).toBe('60 requests per minute');
        });

        it('describes attempts per several minutes', () => {
            expect(describeThrottle('10,5')).toBe('10 requests per 5 minutes');
        });

        it('treats a plain number as one minute', () => {
            expect(describeThrottle('30')).toBe('30 requests per minute');
        });

        it('names a rate limiter', () => {
            expect(describeThrottle('api')).toBe('Rate limited by "api"');
        });

        it('returns null without a throttle', () => {
            expect(describeThrottle(undefined)).toBeNull();
        });
    });

    describe('describeRouteParameter', () => {
        it('combines the model and the pattern', () => {
            const route = routeWith({
                bindings: [
                    { parameter: 'product', model: 'App\\Models\\Product', key: 'slug' },
                ],
                where: { product: '[a-z-]+' },
            });

            expect(describeRouteParameter(route, 'product')).toBe(
                'Product by slug, matches [a-z-]+',
            );
        });

        it('uses the id when the binding has no key', () => {
            const route = routeWith({
                bindings: [{ parameter: 'product', model: 'App\\Product', key: null }],
            });

            expect(describeRouteParameter(route, 'product')).toBe('Product id');
        });

        it('returns null for an unknown parameter', () => {
            expect(describeRouteParameter(routeWith({}), 'product')).toBeNull();
            expect(describeRouteParameter(undefined, 'product')).toBeNull();
        });
    });

    describe('isSignedRoute', () => {
        it('detects the signed middleware', () => {
            const route = routeWith({
                middleware: ['Illuminate\\Routing\\Middleware\\ValidateSignature'],
            });

            expect(isSignedRoute(route)).toBe(true);
        });

        it('detects the signed middleware with an argument', () => {
            const route = routeWith({
                middleware: [
                    'Illuminate\\Routing\\Middleware\\ValidateSignature:relative',
                ],
            });

            expect(isSignedRoute(route)).toBe(true);
        });

        it('is false for other middleware and unknown routes', () => {
            expect(isSignedRoute(routeWith({ middleware: ['auth'] }))).toBe(false);
            expect(isSignedRoute(undefined)).toBe(false);
        });
    });
});
