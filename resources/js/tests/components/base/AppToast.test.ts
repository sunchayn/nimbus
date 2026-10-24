import { useAppToast } from '@/components/base/toast';
import { beforeEach, describe, expect, it, vi } from 'vitest';

/*
 * Fixtures.
 */

const addMock = vi.hoisted(() => vi.fn());

vi.mock('@nuxt/ui/composables/useToast', () => ({
    useToast: () => ({ add: addMock }),
}));

describe('useAppToast', () => {
    beforeEach(() => {
        addMock.mockClear();
    });

    describe('Evaluation', () => {
        it.each(['success', 'warning', 'error'] as const)(
            'adds a %s toast with the matching color',
            color => {
                // Arrange

                const toast = useAppToast();

                // Act

                toast[color]('Title', { description: 'Body', duration: 1000 });

                // Assert

                expect(addMock).toHaveBeenCalledWith({
                    title: 'Title',
                    color,
                    description: 'Body',
                    duration: 1000,
                });
            },
        );

        it('works without options', () => {
            // Arrange

            const toast = useAppToast();

            // Act

            toast.success('Saved');

            // Assert

            expect(addMock).toHaveBeenCalledWith({ title: 'Saved', color: 'success' });
        });
    });
});
