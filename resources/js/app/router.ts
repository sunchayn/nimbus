import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    {
        path: '/',
        component: () => import('@/pages/Main.vue'),
        name: 'main',
    },

    {
        path: '/settings',
        component: () => import('@/pages/Settings.vue'),
        name: 'settings',
    },
    {
        path: '/environments',
        component: () => import('@/pages/Environment.vue'),
        name: 'environments',
    },

    /*
     * Catch-all route for unknown paths.
     * It redirects users to the main page, which prevents blank screens.
     */
    {
        path: '/:pathMatch(.*)*',
        redirect: { name: 'main' },
    },
];

/*
 * The router uses HTML5 history mode and sets the base path from the global Nimbus object.
 * This allows the app to be deployed under a subdirectory.
 */
export default createRouter({
    history: createWebHistory(`${window.Nimbus?.basePath ?? ''}`),
    routes,
});
