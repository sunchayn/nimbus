import RouteExplorer from '@/components/domain/RoutesExplorer/RouteExplorer.vue';
import RoutesListSkeleton from '@/components/layout/skeleton/RoutesListSkeleton.vue';
import { flushPromises, mount } from '@vue/test-utils';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { createPinia } from 'pinia';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

/*
 * Fixtures.
 */

const root = resolve(__dirname, '../../../../..');

const read = (path: string) => readFileSync(resolve(root, path), 'utf8');

describe('Loading skeleton', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    describe('RoutesListSkeleton', () => {
        it('hides the message until the load takes longer than 150 ms', async () => {
            // Arrange

            const wrapper = mount(RoutesListSkeleton);

            // Assert

            expect(wrapper.get('[data-testid="routes-skeleton-message"]').text()).toBe(
                '',
            );

            // Act

            vi.advanceTimersByTime(150);
            await flushPromises();

            // Assert

            expect(wrapper.get('[data-testid="routes-skeleton-message"]').text()).toBe(
                'Loading routes',
            );
        });

        it('adds the first load hint after 3 seconds', async () => {
            // Arrange

            const wrapper = mount(RoutesListSkeleton);

            vi.advanceTimersByTime(2999);
            await flushPromises();

            // Assert

            expect(wrapper.text()).not.toContain('The first load can take longer');

            // Act

            vi.advanceTimersByTime(1);
            await flushPromises();

            // Assert

            expect(wrapper.text()).toContain(
                'The first load can take longer. Later loads are faster.',
            );
        });

        it('announces the message politely', () => {
            // Arrange & Act

            const wrapper = mount(RoutesListSkeleton);

            // Assert

            const message = wrapper.get('[data-testid="routes-skeleton-message"]');

            expect(message.attributes('role')).toBe('status');
            expect(message.attributes('aria-live')).toBe('polite');
        });
    });

    describe('RouteExplorer', () => {
        const createWrapper = (props = {}) =>
            mount(RouteExplorer, {
                props: { routes: null, ...props },
                global: { plugins: [createPinia()] },
            });

        it('shows the skeleton while the routes load', () => {
            // Arrange & Act

            const wrapper = createWrapper({ loading: true });

            // Assert

            expect(wrapper.find('[data-testid="routes-skeleton"]').exists()).toBe(true);
            expect(wrapper.find('[data-testid="routes-error"]').exists()).toBe(false);
        });

        it('offers a retry when the request failed', async () => {
            // Arrange

            const wrapper = createWrapper({ error: 'Network down' });

            // Act

            await wrapper.get('[data-testid="routes-retry"]').trigger('click');

            // Assert

            expect(wrapper.emitted('retry')).toHaveLength(1);
        });
    });

    describe('Static shell', () => {
        it('has the same landmarks as the Vue skeletons', () => {
            // Arrange

            const shell = read('resources/views/partials/loading-shell.blade.php');

            const vue = [
                'MainPageSkeleton',
                'WorkspaceSkeleton',
                'PageSkeleton',
                'RoutesListSkeleton',
            ]
                .map(name => read(`resources/js/components/layout/skeleton/${name}.vue`))
                .join('\n');

            // Act

            const landmarks = [...shell.matchAll(/data-testid="(skeleton-[a-z-]+)"/g)]
                .map(match => match[1])
                .filter(id => id !== 'skeleton-top-bar');

            // Assert

            expect(landmarks.length).toBeGreaterThan(0);

            landmarks.forEach(id => expect(vue).toContain(`data-testid="${id}"`));
        });
    });
});
