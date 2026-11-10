import RoutesListItem from '@/components/domain/RoutesExplorer/RoutesList/RoutesListItem.vue';
import type { RouteDefinition, RouteMetadata } from '@/interfaces/routes/routes';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';

/*
 * Fixtures.
 */

const createRoute = (metadata?: RouteMetadata): RouteDefinition =>
    ({
        name: 'products.show',
        method: 'GET',
        endpoint: 'api/products',
        shortEndpoint: 'api/products',
        schema: { shape: { type: 'object' }, extractionErrors: null },
        metadata,
    }) as unknown as RouteDefinition;

const createWrapper = (metadata?: RouteMetadata) =>
    mount(RoutesListItem, {
        props: { route: createRoute(metadata), resource: 'api', isActive: false },
    });

describe('RoutesListItem', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    describe('Throttle', () => {
        it('shows a throttle icon when the route has a limit', () => {
            // Act

            const wrapper = createWrapper({ throttle: '30,1' });

            // Assert

            expect(wrapper.find('[data-testid="route-throttle-badge"]').exists()).toBe(
                true,
            );
        });

        it('shows no throttle icon without a limit', () => {
            // Act

            const wrapper = createWrapper({});

            // Assert

            expect(wrapper.find('[data-testid="route-throttle-badge"]').exists()).toBe(
                false,
            );
        });
    });
});
