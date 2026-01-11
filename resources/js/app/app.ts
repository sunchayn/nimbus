/**
 * Main Application Entry Point
 *
 * This file serves as the primary entry point for the Vue.js application.
 * It initializes the Vue app with all necessary plugins, stores, and routing.
 */

/*
 * CSS & Bootstrap Imports.
 */

import '~/app.css';
import './bootstrap';

/*
 * Core Vue Dependencies.
 */

import { createPinia } from 'pinia';
import { createApp } from 'vue';

/*
 * Application Components & Configuration.
 */

import { createPersistedState } from 'pinia-plugin-persistedstate';
import App from './App.vue';
import router from './router';

/**
 * Application Initialization.
 * Initialize and mount the Vue application
 *
 * This creates a new Vue app instance with:
 * - Pinia store for state management
 * - Vue Router for client-side routing
 * - Main App component as the root component
 */

const app = createApp(App);

// Configure application plugins
const pinia = createPinia();
app.use(pinia);
pinia.use(
    createPersistedState({
        key: (id: string) => `nimbus:${id}`,
    }),
);
app.use(router);

// Mount the application to the DOM
app.mount('#app');
