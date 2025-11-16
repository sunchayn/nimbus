import { useResponsiveResizable } from '@/composables/ui/useResponsiveResizable';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

const resizeObserverMock = vi.hoisted(() => vi.fn());

vi.mock('@vueuse/core', () => ({
    useResizeObserver: resizeObserverMock,
}));

describe('useResponsiveResizable', () => {
    beforeEach(() => {
        resizeObserverMock.mockClear();
    });

    it('computes layout direction based on current width', () => {
        const element = ref({
            $el: {
                contentRect: {
                    width: 800,
                },
            },
        });

        const { thresholds } = useResponsiveResizable([600, 1000], element);

        expect(thresholds[0].value).toBe('horizontal');
        expect(thresholds[1].value).toBe('vertical');

        const callback = resizeObserverMock.mock.calls[0][1];

        callback([{ contentRect: { width: 500 } }]);

        expect(thresholds[0].value).toBe('vertical');
        expect(thresholds[1].value).toBe('vertical');
    });
});
