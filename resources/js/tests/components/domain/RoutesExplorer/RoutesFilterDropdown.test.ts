import RoutesFilterDropdown from '@/components/domain/RoutesExplorer/RoutesFilterDropdown.vue';
import type { RouteDefinition } from '@/interfaces/routes/routes';
import { emptyRouteFilters, type RouteFilters } from '@/utils/routes';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeAll, describe, expect, it, vi } from 'vitest';

/*
 * Fixtures.
 */

const createRoute = (method: string, middleware: string[] = []): RouteDefinition =>
    ({
        method,
        endpoint: `api/${method}/${middleware.join('-')}`,
        metadata: { middleware, auth: middleware.includes('auth') ? 'required' : 'none' },
    }) as unknown as RouteDefinition;

const routes = [
    createRoute('GET'),
    createRoute('GET', ['auth']),
    createRoute('POST', ['auth', 'throttle:60,1']),
];

const createWrapper = (modelValue: RouteFilters = emptyRouteFilters()) =>
    mount(RoutesFilterDropdown, {
        props: {
            modelValue,
            routes,
            methods: ['GET', 'POST'],
            middleware: ['auth', 'throttle:60,1'],
        },
        attachTo: document.body,
    });

const openDropdown = async (wrapper: ReturnType<typeof createWrapper>) => {
    await wrapper.get('[data-testid="routes-filter-toggle"]').trigger('click');
    await flushPromises();
};

const options = () =>
    Array.from(document.body.querySelectorAll('[role="option"]')) as HTMLElement[];

const optionNamed = (label: string) =>
    options().find(option => option.textContent?.includes(label));

describe('RoutesFilterDropdown', () => {
    beforeAll(() => {
        // jsdom does not implement scrollIntoView, which the listbox calls on highlight.
        Element.prototype.scrollIntoView = vi.fn();
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('lists the methods, the access filter and the middleware with route counts', async () => {
        // Arrange

        const wrapper = createWrapper();

        // Act

        await openDropdown(wrapper);

        // Assert

        expect(optionNamed('GET')?.textContent).toContain('2');
        expect(optionNamed('POST')?.textContent).toContain('1');
        expect(optionNamed('Requires auth')?.textContent).toContain('2');
        expect(optionNamed('throttle:60,1')).toBeDefined();
        wrapper.unmount();
    });

    it('emits the filters when a method is selected', async () => {
        // Arrange

        const wrapper = createWrapper();
        await openDropdown(wrapper);

        // Act

        optionNamed('POST')?.click();
        await flushPromises();

        // Assert

        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([
            { ...emptyRouteFilters(), methods: ['POST'] },
        ]);
        wrapper.unmount();
    });

    it('removes a method that is already selected', async () => {
        // Arrange

        const wrapper = createWrapper({ ...emptyRouteFilters(), methods: ['GET'] });
        await openDropdown(wrapper);

        // Act

        optionNamed('GET')?.click();
        await flushPromises();

        // Assert

        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([emptyRouteFilters()]);
        wrapper.unmount();
    });

    it('narrows the options with the search input', async () => {
        // Arrange

        const wrapper = createWrapper();
        await openDropdown(wrapper);
        const input = document.body.querySelector('input') as HTMLInputElement;

        // Act

        input.value = 'post';
        input.dispatchEvent(new Event('input', { bubbles: true }));
        await flushPromises();

        // Assert

        expect(options().map(option => option.textContent)).toEqual([
            expect.stringContaining('POST'),
        ]);
        wrapper.unmount();
    });

    it('shows the route count of each option as a chip', async () => {
        // Arrange

        const wrapper = createWrapper();

        // Act

        await openDropdown(wrapper);

        // Assert

        const chips = Array.from(
            document.body.querySelectorAll('[data-testid="routes-filter-count"]'),
        ).map(chip => chip.textContent?.trim());

        expect(chips).toEqual(['2', '1', '2', '2', '1']);
        wrapper.unmount();
    });

    it('shows the number of applied filters as a chip on the toggle', async () => {
        // Arrange

        const wrapper = createWrapper({
            ...emptyRouteFilters(),
            methods: ['GET', 'POST'],
        });

        // Assert

        expect(wrapper.get('[data-testid="routes-filter-active-count"]').text()).toBe(
            '2',
        );
        wrapper.unmount();
    });

    it('shows no chip on the toggle when no filter is applied', () => {
        // Arrange

        const wrapper = createWrapper();

        // Assert

        expect(wrapper.find('[data-testid="routes-filter-active-count"]').exists()).toBe(
            false,
        );
        wrapper.unmount();
    });

    it('aligns the empty message to the start', async () => {
        // Arrange

        const wrapper = createWrapper();
        await openDropdown(wrapper);
        const input = document.body.querySelector('input') as HTMLInputElement;

        // Act

        input.value = 'no such filter';
        input.dispatchEvent(new Event('input', { bubbles: true }));
        await flushPromises();

        // Assert

        // Outer wrappers share the text, so the innermost element is the message itself.
        const empty = Array.from(document.body.querySelectorAll('div'))
            .filter(element => element.textContent === 'No filter matches your search.')
            .at(-1);

        expect(empty).toBeDefined();
        expect(empty?.className).toContain('text-start');
        expect(empty?.className).not.toContain('text-center');
        wrapper.unmount();
    });

    it('shows the number of applied filters', async () => {
        // Arrange

        const wrapper = createWrapper({
            methods: ['GET'],
            requiresAuth: true,
            middleware: [],
        });

        // Act

        await openDropdown(wrapper);

        // Assert

        expect(wrapper.get('[data-testid="routes-filter-toggle"]').text()).toContain('2');
        expect(document.body.textContent).toContain('2 filters applied');
        wrapper.unmount();
    });

    it('clears the applied filters', async () => {
        // Arrange

        const wrapper = createWrapper({
            methods: ['GET'],
            requiresAuth: true,
            middleware: [],
        });
        await openDropdown(wrapper);

        // Act

        document.body
            .querySelector<HTMLElement>('[data-testid="routes-filter-clear"]')
            ?.click();
        await flushPromises();

        // Assert

        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([emptyRouteFilters()]);
        wrapper.unmount();
    });

    it('does not offer to clear when no filter is applied', async () => {
        // Arrange

        const wrapper = createWrapper();

        // Act

        await openDropdown(wrapper);

        // Assert

        expect(
            document.body.querySelector('[data-testid="routes-filter-clear"]'),
        ).toBeNull();
        expect(document.body.textContent).not.toContain('No filters applied');
        expect(
            document.body.querySelector('[data-testid="routes-filters-footer"]'),
        ).toBeNull();
        wrapper.unmount();
    });
});
