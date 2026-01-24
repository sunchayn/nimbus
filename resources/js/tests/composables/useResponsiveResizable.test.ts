import { useResponsiveResizable } from '@/composables/ui/useResponsiveResizable';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

/*
 * Fixtures.
 */

const resizeObserverMock = vi.hoisted(() => vi.fn());

vi.mock('@vueuse/core', () => ({
    useResizeObserver: resizeObserverMock,
}));

describe('useResponsiveResizable', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    /*
     * Evaluation tests.
     */

    describe('Evaluation', () => {
        it('computes layout direction based on current width', () => {
            // Arrange

            const element = ref({
                $el: { contentRect: { width: 800 } },
            });

            // Act

            const { thresholds } = useResponsiveResizable([600, 1000], element);

            // Assert

            expect(thresholds[0].value).toBe('horizontal');
            expect(thresholds[1].value).toBe('vertical');

            // Act - Trigger Resize

            const callback = resizeObserverMock.mock.calls[0][1];
            callback([{ contentRect: { width: 500 } }]);

            // Assert

            expect(thresholds[0].value).toBe('vertical');
        });
    });
});
