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



    /*
     * Catch-all Route for 404 Handling.
     *
     * This route matches any path that does not correspond to a defined route.
     * Ensures users navigating to unknown URLs are redirected to the main page,
     * preventing blank screens or unhandled errors.
     */
    {
        path: '/:pathMatch(.*)*',
        redirect: '/',
    },
];

/*
 * Vue Router Instance.
 *
 * The router uses HTML5 history mode and dynamically sets the base path
 * from the global Nimbus object if available. This allows the app to be
 * deployed under a subdirectory without breaking navigation.
 */
export default createRouter({
    history: createWebHistory(`${window.Nimbus?.basePath ?? ''}`),
    routes,
});
