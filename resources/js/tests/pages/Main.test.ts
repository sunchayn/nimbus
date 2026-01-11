import { AppSidebarProvider } from '@/components/base/sidebar';
import { RouteExtractorException, RoutesGroup } from '@/interfaces';
import MainPage from '@/pages/Main.vue';
import { mountWithPlugins } from '@/tests/_utils/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { h, nextTick } from 'vue';

// Mock the stores
const mockRoutesStore: {
    routes: { [key: string]: RoutesGroup[] } | null;
    hasExtractionError: boolean;
    routeExtractorException: RouteExtractorException | null;
    initializeRoutes: () => void;
} = {
    routes: {
        v1: [
            {
                resource: 'users',
                routes: [
                    {
                        method: 'GET',
                        endpoint: 'api/users',
                        shortEndpoint: 'api/users',
                        schema: {
                            shape: {},
                            extractionErrors: null,
                        },
                    },
                    {
                        method: 'POST',
                        endpoint: 'api/users',
                        shortEndpoint: 'api/users',
                        schema: {
                            shape: {},
                            extractionErrors: null,
                        },
                    },
                ],
            },
        ],
    },
    hasExtractionError: false,
    routeExtractorException: null,
    initializeRoutes: vi.fn(),
};

const mockConfigStore = {
    apiUrl: 'https://api.example.com',
    headers: [],
};

const mockValueGeneratorStore = {
    openCommand: vi.fn(),
    closeCommand: vi.fn(),
};

vi.mock('@/stores', async () => {
    // Import the real module first
    const original: object = await vi.importActual('@/stores');

    return {
        ...original,
        useRoutesStore: () => mockRoutesStore,
        useConfigStore: () => mockConfigStore,
        useValueGeneratorStore: () => mockValueGeneratorStore,
    };
});

const componentFactory = () => {
    return mountWithPlugins({
        // We to wrap MainPage inside an `AppSidebarProvider` component (normally provided by App.vue)
        render() {
            return h(
                AppSidebarProvider,
                {},
                {
                    default: () => h(MainPage),
                },
            );
        },
    });
};

describe('MainPage', () => {
    afterEach(() => {
        mockRoutesStore.hasExtractionError = false;
    });

    it('renders RouteExplorer with routes data', () => {
        const wrapper = componentFactory();

        const routeExplorer = wrapper.findComponent({ name: 'RouteExplorer' });
        expect(routeExplorer.exists()).toBe(true);
        expect(routeExplorer.props('routes')).toBe(mockRoutesStore.routes);
    });

    it('renders RequestBuilder and ResponseViewer when no extraction error', () => {
        mockRoutesStore.hasExtractionError = false;
        const wrapper = componentFactory();

        expect(wrapper.findComponent({ name: 'RequestBuilder' }).exists()).toBe(true);
        expect(wrapper.findComponent({ name: 'ResponseViewer' }).exists()).toBe(true);
    });

    it('renders RouteExtractorExceptionRenderer instead of request/response components when extraction error exists', () => {
        mockRoutesStore.hasExtractionError = true;
        mockRoutesStore.routeExtractorException = {
            exception: {
                message: 'Extraction failed',
            },
            routeContext: {},
        };

        const wrapper = componentFactory();

        expect(
            wrapper.findComponent({ name: 'RouteExtractorExceptionRenderer' }).exists(),
        ).toBe(true);
        expect(wrapper.findComponent({ name: 'RequestBuilder' }).exists()).toBe(false);
        expect(wrapper.findComponent({ name: 'ResponseViewer' }).exists()).toBe(false);
    });

    it('passes correct props to RouteExtractorExceptionRenderer', () => {
        const mockException: RouteExtractorException = {
            exception: {
                message: 'Test error',
            },
            routeContext: {},
        };

        mockRoutesStore.hasExtractionError = true;
        mockRoutesStore.routeExtractorException = mockException;

        const wrapper = componentFactory();

        const exceptionRenderer = wrapper.findComponent({
            name: 'RouteExtractorExceptionRenderer',
        });

        expect(exceptionRenderer.props('error')).toBe(mockException);
    });

    it('calls initializeRoutes on mount', async () => {
        componentFactory();

        await nextTick();

        expect(mockRoutesStore.initializeRoutes).toHaveBeenCalled();
    });

    it('configures resizable panels with correct sizing constraints', () => {
        const wrapper = componentFactory();

        const panelGroup = wrapper.findComponent({
            name: 'AppResizablePanelGroup',
        });

        expect(panelGroup.props('autoSaveId')).toBe('main-splitter-group');
        expect(panelGroup.props('direction')).toBe('vertical');

        const panels = wrapper.findAllComponents({ name: 'AppResizablePanel' });
        expect(panels).toHaveLength(4);

        expect(panels[0].props('minSize')).toBe(15);
        expect(panels[0].props('defaultSize')).toBe(20);
        expect(panels[1].props('minSize')).toBe(60);
        expect(panels[1].props('defaultSize')).toBe(80);
    });

    it('handles null routes data gracefully', () => {
        mockRoutesStore.routes = null;
        const wrapper = componentFactory();

        expect(
            wrapper.findComponent({ name: 'RouteExplorer' }).props('routes'),
        ).toBeNull();
    });

    it('reactively updates UI when extraction error state changes', async () => {
        const wrapper = componentFactory();

        expect(wrapper.findComponent({ name: 'RequestBuilder' }).exists()).toBe(true);

        mockRoutesStore.hasExtractionError = true;
        mockRoutesStore.routeExtractorException = {
            exception: {
                message: 'Error',
            },
            routeContext: {},
        };

        await wrapper.vm.$nextTick();

        expect(
            wrapper.findComponent({ name: 'RouteExtractorExceptionRenderer' }).exists(),
        ).toBe(true);
        expect(wrapper.findComponent({ name: 'RequestBuilder' }).exists()).toBe(false);
    });
});
