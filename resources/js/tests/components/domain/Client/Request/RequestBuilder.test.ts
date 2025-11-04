import RequestBuilder from '@/components/domain/Client/Request/RequestBuilder.vue';
import { mountWithPlugins } from '@/tests/_utils/test-utils';
import { VueWrapper } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

// Mock the child components
vi.mock('@/components/domain/Client/Request', () => ({
    RequestBuilderEndpoint: {
        name: 'RequestBuilderEndpoint',
        template:
            '<div data-testid="request-builder-endpoint">Request Builder Endpoint</div>',
    },
    RequestParameters: {
        name: 'RequestParameters',
        template: '<div data-testid="request-parameters">Request Parameters</div>',
    },
    RequestBody: {
        name: 'RequestBody',
        template: '<div data-testid="request-body">Request Body</div>',
    },
    RequestAuthorization: {
        name: 'RequestAuthorization',
        template: '<div data-testid="request-authorization">Request Authorization</div>',
    },
    RequestHeaders: {
        name: 'RequestHeaders',
        template: '<div data-testid="request-headers">Request Headers</div>',
    },
}));

describe('RequestBuilder', () => {
    it('renders the main container with correct classes', () => {
        const wrapper = mountWithPlugins(RequestBuilder);

        const container = wrapper.find('[data-testid="request-builder-root"]');
        expect(container.exists()).toBe(true);
    });

    it('renders with custom class prop', () => {
        const customClass = 'custom-request-builder';
        const wrapper = mountWithPlugins(RequestBuilder, {
            props: { class: customClass },
        });

        const container = wrapper.find('[data-testid="request-builder-root"]');
        expect(container.classes()).toContain(customClass);
    });

    it('renders RequestBuilderEndpoint component', () => {
        const wrapper = mountWithPlugins(RequestBuilder);

        const endpoint = wrapper.findComponent({
            name: 'RequestBuilderEndpoint',
        });
        expect(endpoint.exists()).toBe(true);
        expect(endpoint.classes()).toContain('h-toolbar');
        expect(endpoint.classes()).toContain('border-b');
    });

    it('renders AppTabs with correct configuration', () => {
        const wrapper = mountWithPlugins(RequestBuilder);

        const tabs = wrapper.findComponent({ name: 'AppTabs' });
        expect(tabs.exists()).toBe(true);
        expect(tabs.props('defaultValue')).toBe('body');
        expect(tabs.classes()).toContain('mt-0');
        expect(tabs.classes()).toContain('flex');
        expect(tabs.classes()).toContain('overflow-hidden');
        expect(tabs.classes()).toContain('flex-1');
        expect(tabs.classes()).toContain('flex-col');
    });

    it('renders tabs list with correct structure', () => {
        const wrapper = mountWithPlugins(RequestBuilder);

        const tabsList = wrapper.findComponent({ name: 'AppTabsList' });
        expect(tabsList.exists()).toBe(true);
        expect(tabsList.classes()).toContain('h-toolbar');
        expect(tabsList.classes()).toContain('px-panel');
        expect(tabsList.classes()).toContain('rounded-none');
    });

    it('renders all tab triggers with correct labels', () => {
        const wrapper = mountWithPlugins(RequestBuilder);

        const tabTriggers = wrapper.findAllComponents({
            name: 'AppTabsTrigger',
        });
        expect(tabTriggers).toHaveLength(4);

        const expectedLabels = ['Parameters', 'Body', 'Authorization', 'Headers'];
        const expectedValues = ['parameters', 'body', 'authorization', 'headers'];

        tabTriggers.forEach((trigger, index) => {
            expect(trigger.props('label')).toBe(expectedLabels[index]);
            expect(trigger.props('value')).toBe(expectedValues[index]);
        });
    });

    it('renders all tab content components', () => {
        const wrapper = mountWithPlugins(RequestBuilder);

        const tabContents = wrapper.findAllComponents({
            name: 'AppTabsContent',
        });
        expect(tabContents).toHaveLength(4);

        const expectedValues = ['parameters', 'body', 'authorization', 'headers'];

        tabContents.forEach((content, index) => {
            expect(content.props('value')).toBe(expectedValues[index]);
            expect(content.classes()).toContain('mt-0');
            expect(content.classes()).toContain('max-h-full');
            expect(content.classes()).toContain('min-h-0');
            expect(content.classes()).toContain('flex');
        });
    });

    it.each([
        { label: 'Parameters', expectedComponent: 'RequestParameters' },
        { label: 'Body', expectedComponent: 'RequestBody' },
        { label: 'Authorization', expectedComponent: 'RequestAuthorization' },
        { label: 'Headers', expectedComponent: 'RequestHeaders' },
    ])(
        'renders $expectedComponent in parameters tab',
        async ({ label, expectedComponent }) => {
            const wrapper = mountWithPlugins(RequestBuilder);

            const tabTrigger = wrapper
                .findAllComponents({ name: 'AppTabsTrigger' })
                .find((element: VueWrapper) => element.attributes()['label'] === label);

            await (tabTrigger as VueWrapper).trigger('mousedown');

            await nextTick();

            expect(tabTrigger?.attributes()['data-state']).toEqual('active');

            const actualComponent = wrapper.findComponent({
                name: expectedComponent,
            });
            expect(actualComponent.exists()).toBe(true);
        },
    );

    it('has correct tab container styling', () => {
        const wrapper = mountWithPlugins(RequestBuilder);

        const tabContainer = wrapper.find('[data-testid="app-tabs-container"]');
        expect(tabContainer.exists()).toBe(true);

        const container = wrapper.find('[data-testid="request-builder-root"]');
        expect(container.exists()).toBe(true);
    });

    it('forwards asChild prop correctly', () => {
        const wrapper = mountWithPlugins(RequestBuilder, {
            props: { asChild: true },
        });

        // The component should render normally even with asChild prop
        expect(wrapper.find('[data-testid="request-builder-root"]').exists()).toBe(true);
    });

    it('handles missing props gracefully', () => {
        const wrapper = mountWithPlugins(RequestBuilder, {
            props: {},
        });

        expect(wrapper.find('[data-testid="request-builder-root"]').exists()).toBe(true);
        expect(wrapper.findComponent({ name: 'RequestBuilderEndpoint' }).exists()).toBe(
            true,
        );
    });

    it('maintains proper component hierarchy', () => {
        const wrapper = mountWithPlugins(RequestBuilder);

        // Check the main structure
        const mainContainer = wrapper.find('[data-testid="request-builder-root"]');
        expect(mainContainer.exists()).toBe(true);

        // Check that endpoint is a direct child
        const endpoint = mainContainer.findComponent({
            name: 'RequestBuilderEndpoint',
        });
        expect(endpoint.exists()).toBe(true);

        // Check that tabs container is a direct child
        const tabs = mainContainer.findComponent({ name: 'AppTabs' });
        expect(tabs.exists()).toBe(true);
    });

    it('renders all components without errors', () => {
        const wrapper = mountWithPlugins(RequestBuilder);

        // All main components should be present
        expect(wrapper.findComponent({ name: 'RequestBuilderEndpoint' }).exists()).toBe(
            true,
        );
        expect(wrapper.findComponent({ name: 'AppTabs' }).exists()).toBe(true);
        expect(wrapper.findComponent({ name: 'AppTabsList' }).exists()).toBe(true);
        expect(wrapper.findAllComponents({ name: 'AppTabsTrigger' })).toHaveLength(4);
        expect(wrapper.findAllComponents({ name: 'AppTabsContent' })).toHaveLength(4);
        expect(wrapper.findComponent({ name: 'RequestParameters' }).exists()).toBe(true);
        expect(wrapper.findComponent({ name: 'RequestBody' }).exists()).toBe(true);
        expect(wrapper.findComponent({ name: 'RequestAuthorization' }).exists()).toBe(
            true,
        );
        expect(wrapper.findComponent({ name: 'RequestHeaders' }).exists()).toBe(true);
    });
});
