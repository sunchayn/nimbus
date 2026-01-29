import { AppSidebarProvider } from '@/components/base/sidebar';
import type { GlobalException, RouteExtractorException, RoutesGroup } from '@/interfaces';
import MainPage from '@/pages/Main.vue';
import type { VueWrapper } from '@vue/test-utils';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { h, nextTick, reactive } from 'vue';

/*
 * Fixtures.
 */

const mockRoutesStore = reactive({
    routes: {
        v1: [
            {
                resource: 'users',
                routes: [
                    {
                        method: 'GET',
                        endpoint: 'api/users',
                        shortEndpoint: 'api/users',
                        schema: { shape: {}, extractionErrors: null },
                    },
                ],
            },
        ],
    } as { [key: string]: RoutesGroup[] } | null,
    hasExtractionError: false,
    hasAnyError: false,
    routeExtractorException: null as RouteExtractorException | null,
    initializeRoutes: vi.fn(),
    isMissingImplementation: vi.fn().mockReturnValue(false),
    isUndocumented: vi.fn().mockReturnValue(false),
});

const mockErrorStore = reactive({
    globalError: null as GlobalException | null,
    hasGlobalError: false,
    initializeGlobalErrors: vi.fn(),
    clearGlobalError: vi.fn(),
});

const mockConfigStore = {
    apiUrl: 'https://api.example.com',
    headers: [],
    applications: {},
    isVersioned: false,
    activeApplication: null,
};

const mockValueGeneratorStore = {
    openCommand: vi.fn(),
    closeCommand: vi.fn(),
};

vi.mock('@/stores', async () => {
    const original: object = await vi.importActual('@/stores');

    return {
        ...original,
        useRoutesStore: () => mockRoutesStore,
        useConfigStore: () => mockConfigStore,
        useErrorStore: () => mockErrorStore,
        useValueGeneratorStore: () => mockValueGeneratorStore,
    };
});

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */
const createWrapper = (options = {}): VueWrapper => {
    return mount(AppSidebarProvider, {
        slots: {
            default: h(MainPage),
        },
        ...options,
        global: {
            plugins: [createPinia()],
            // @ts-expect-error .global not found in object.
            ...(options.global || {}),
        },
    });
};

describe('MainPage', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    afterEach(() => {
        mockRoutesStore.hasExtractionError = false;
        mockRoutesStore.routes = {
            v1: [
                {
                    resource: 'users',
                    routes: [
                        {
                            method: 'GET',
                            endpoint: 'api/users',
                            shortEndpoint: 'api/users',
                            schema: { shape: {}, extractionErrors: null },
                        },
                    ],
                },
            ],
        };
        mockRoutesStore.hasAnyError = false;
        mockErrorStore.hasGlobalError = false;
        mockErrorStore.globalError = null;
    });

    /*
     * Rendering tests.
     */

    describe('Rendering', () => {
        it('renders RouteExplorer with routes data', () => {
            // Arrange

            const wrapper = createWrapper();

            // Assert

            const routeExplorer = wrapper.findComponent({ name: 'RouteExplorer' });
            expect(routeExplorer.exists()).toBe(true);
            expect(routeExplorer.props('routes')).toBe(mockRoutesStore.routes);
        });

        it('renders RequestBuilder and ResponseViewer when no extraction error', () => {
            // Arrange

            mockRoutesStore.hasExtractionError = false;
            const wrapper = createWrapper();

            // Assert

            expect(wrapper.findComponent({ name: 'RequestBuilder' }).exists()).toBe(true);
            expect(wrapper.findComponent({ name: 'ResponseViewer' }).exists()).toBe(true);
        });

        it('renders RouteExtractorExceptionRenderer instead of request/response components when extraction error exists', () => {
            // Arrange

            mockRoutesStore.hasExtractionError = true;
            mockRoutesStore.hasAnyError = true;
            mockRoutesStore.routeExtractorException = {
                exception: { message: 'Extraction failed' },
                routeContext: {},
            };

            const wrapper = createWrapper();

            // Assert

            expect(
                wrapper
                    .findComponent({ name: 'RouteExtractorExceptionRenderer' })
                    .exists(),
            ).toBe(true);
            expect(wrapper.findComponent({ name: 'RequestBuilder' }).exists()).toBe(
                false,
            );
            expect(wrapper.findComponent({ name: 'ResponseViewer' }).exists()).toBe(
                false,
            );
        });

        it('renders GlobalErrorRenderer when global error exists', () => {
            // Arrange

            mockErrorStore.hasGlobalError = true;
            mockErrorStore.globalError = {
                exception: { message: 'Global error' },
            };

            const wrapper = createWrapper();

            // Assert

            expect(wrapper.findComponent({ name: 'GlobalErrorRenderer' }).exists()).toBe(
                true,
            );
            expect(wrapper.findComponent({ name: 'RequestBuilder' }).exists()).toBe(
                false,
            );
        });
    });

    /*
     * State Transition tests.
     */

    describe('Behavior', () => {
        it('calls initializeRoutes on mount', async () => {
            // Arrange & Act

            createWrapper();
            await nextTick();

            // Assert

            expect(mockErrorStore.initializeGlobalErrors).toHaveBeenCalled();
            expect(mockRoutesStore.initializeRoutes).toHaveBeenCalled();
        });

        it('reactively updates UI when extraction error state changes', async () => {
            // Arrange

            mockRoutesStore.routes = { v1: [] };
            mockRoutesStore.hasAnyError = false;
            mockRoutesStore.hasExtractionError = false;

            const wrapper = createWrapper();
            expect(wrapper.findComponent({ name: 'RequestBuilder' }).exists()).toBe(true);

            // Act

            mockRoutesStore.hasExtractionError = true;
            mockRoutesStore.hasAnyError = true;
            mockRoutesStore.routeExtractorException = {
                exception: { message: 'Updated Error' },
                routeContext: {},
            };
            await nextTick();
            await nextTick();

            // Assert

            expect(
                wrapper
                    .findComponent({ name: 'RouteExtractorExceptionRenderer' })
                    .exists(),
            ).toBe(true);
            expect(wrapper.findComponent({ name: 'RequestBuilder' }).exists()).toBe(
                false,
            );
        });
    });
});
