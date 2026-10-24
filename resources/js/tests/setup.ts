import ui from '@nuxt/ui/vue-plugin';
import '@testing-library/jest-dom';
import { config, mount } from '@vue/test-utils';
import { setActivePinia } from 'pinia';
import { TooltipProvider } from 'reka-ui';
import { beforeEach, vi } from 'vitest';
import { getCurrentInstance, h } from 'vue';

import { createTestingPinia } from '@pinia/testing';

/*
 * Install Nuxt UI for every mount, so base components and UIcon resolve their injections.
 * Tests can still pass their own plugins in `global.plugins`, which are merged with this one.
 */
config.global.plugins.push(ui);

/*
 * UTooltip injects a provider that only UApp renders.
 * A real provider is mounted once and its context is provided to every mount,
 * so trees with tooltips work without wrapping each test in UApp.
 * A test that mounts UApp still gets its own nested provider.
 */
let captured: Record<symbol, unknown> = {};

// A functional component runs inside the provider, so its instance sees the provided context.
const captureProvides = (): null => {
    captured = (getCurrentInstance() as unknown as { provides: Record<symbol, unknown> })
        .provides;

    return null;
};

mount(TooltipProvider, { slots: { default: () => h(captureProvides) } });

config.global.provide = Object.fromEntries(
    Object.getOwnPropertySymbols(captured)
        .filter(key => key.description === 'TooltipProviderContext')
        .map(key => [key, captured[key]]),
);

beforeEach(() => {
    // Reset Pinia stores before each test
    setActivePinia(createTestingPinia({ stubActions: false }));

    // Clear all mocks
    vi.clearAllMocks();
});

// Mock console methods to reduce noise in tests
global.console = {
    ...console,
    warn: vi.fn(),
    error: vi.fn(),
};
