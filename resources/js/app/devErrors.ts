/*
 * Dev-only error simulation.
 *
 * Open the app with `?simulate-error=global` or `?simulate-error=route`.
 * The whole block is stripped from production builds by `import.meta.env.DEV`.
 */

import type { GlobalException, RouteExtractorException } from '@/interfaces';

const previous = {
    message: 'Simulated previous exception.',
    file: '/app/Http/Controllers/UserController.php',
    line: 42,
    trace: '#0 /vendor/laravel/framework/src/Foo.php(10)<br/>#1 {main}',
};

const globalException: GlobalException = {
    exception: { message: 'Simulated global error.', previous },
    suggestedSolution: 'This is a simulated suggestion.',
};

const routeException: RouteExtractorException = {
    exception: { message: 'Simulated route extraction error.', previous },
    routeContext: {
        uri: 'api/users/{user}',
        methods: ['GET', 'HEAD'],
        controllerClass: 'App\\Http\\Controllers\\UserController',
        controllerMethod: 'show',
    },
    suggestedSolution: 'This is a simulated suggestion.',
    ignoreData: 'api/users/{user}|["GET","HEAD"]',
};

if (import.meta.env.DEV) {
    const kind = new URLSearchParams(window.location.search).get('simulate-error');

    if (kind === 'global') {
        window.Nimbus.globalException = JSON.stringify(globalException);
    } else if (kind === 'route') {
        window.Nimbus.routeExtractorException = JSON.stringify(routeException);
    }
}
