import { useRouteStatistics } from '@/composables/data/useRouteStatistics';
import { useRoutesStore } from '@/stores';
import { createMockRouteDefinition } from '@/tests/_utils/test-factories';
import { beforeEach, describe, expect, it, vi } from 'vitest';

/*
 * Fixtures.
 */

vi.mock('@/stores', () => ({
    useRoutesStore: vi.fn(),
}));

const mockRoutesStore = {
    routes: null as ReturnType<typeof useRoutesStore>['routes'],
};

describe('useRouteStatistics', () => {
    beforeEach(() => {
        vi.mocked(useRoutesStore).mockReturnValue(
            mockRoutesStore as unknown as ReturnType<typeof useRoutesStore>,
        );
        mockRoutesStore.routes = null;
        vi.clearAllMocks();
    });

    /*
     * Evaluation tests.
     */

    describe('Evaluation', () => {
        it('returns zero statistics when no routes exist', () => {
            // Arrange

            mockRoutesStore.routes = null;

            // Act

            const { routeStatistics } = useRouteStatistics();

            // Assert

            expect(routeStatistics.value.total).toBe(0);
        });

        it('calculates statistics correctly for mixed routes', () => {
            // Arrange

            mockRoutesStore.routes = {
                v1: [
                    {
                        resource: 'users',
                        routes: [
                            createMockRouteDefinition({
                                schema: { shape: {}, extractionErrors: 'Err' },
                            }),
                            createMockRouteDefinition({ method: 'POST' }),
                        ],
                    },
                ],
            };

            // Act

            const { routeStatistics } = useRouteStatistics();

            // Assert

            expect(routeStatistics.value.total).toBe(2);
            expect(routeStatistics.value.withErrors).toBe(1);
            expect(routeStatistics.value.errorRate).toBe(50);
        });
    });
});
