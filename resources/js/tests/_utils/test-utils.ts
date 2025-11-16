import userEvent from '@testing-library/user-event';
import { render, RenderOptions, screen } from '@testing-library/vue';
import type { MountingOptions } from '@vue/test-utils';
import { mount, VueWrapper } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { Component } from 'vue';
import { createRouter, createWebHistory, Router } from 'vue-router';

export interface RenderWithProvidersOptions extends RenderOptions<unknown> {
    router?: Router;
}

export function createMockRouter(): Router {
    return createRouter({
        history: createWebHistory(),
        routes: [
            {
                path: '/',
                name: 'home',
                component: { template: '<div>Home</div>' },
            },
            {
                path: '/main',
                name: 'main',
                component: { template: '<div>Main</div>' },
            },
            {
                path: '/status',
                name: 'status',
                component: { template: '<div>Status</div>' },
            },
        ],
    });
}

export function renderWithProviders(
    component: Component,
    options: RenderWithProvidersOptions = {},
) {
    const pinia = createPinia();
    setActivePinia(pinia);

    const router = options.router ?? createMockRouter();

    return {
        user: userEvent.setup(),
        ...render(component, {
            ...options,
            global: {
                ...(options.global ?? {}),
                plugins: [...(options.global?.plugins ?? []), pinia, router],
                stubs: {
                    keepAlive: true,
                    Transition: true,
                    Teleport: true,
                    'router-link': true,
                    'router-view': true,
                    ...(options.global?.stubs ?? {}),
                },
            },
        }),
    };
}

export function mountWithPlugins(
    component: Component,
    options: MountingOptions<unknown> & { router?: Router } = {},
): VueWrapper<unknown> {
    const pinia = createPinia();
    setActivePinia(pinia);

    const router = options.router ?? createMockRouter();

    return mount(component, {
        ...options,
        global: {
            ...(options.global ?? {}),
            plugins: [...(options.global?.plugins ?? []), pinia, router],
            stubs: {
                keepAlive: true,
                Transition: true,
                Teleport: true,
                'router-link': true,
                'router-view': true,
                ...(options.global?.stubs ?? {}),
            },
        },
    });
}

export { screen };
