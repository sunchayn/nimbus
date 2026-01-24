import { useRouteSegmentSelection } from '@/composables/request/useRouteSegmentSelection';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick, ref } from 'vue';

/*
 * Fixtures.
 */

describe('useRouteSegmentSelection', () => {
    /*
     * Identification tests.
     */

    describe('Segment Identification', () => {
        it('identifies single variable segment', () => {
            // Arrange

            const endpoint = ref('api/users/{id}');
            const { identifyVariableSegments } = useRouteSegmentSelection({ endpoint });

            // Act

            const result = identifyVariableSegments('api/users/{id}');

            // Assert

            expect(result).toEqual([2]);
        });

        it('identifies multiple variable segments', () => {
            // Arrange

            const endpoint = ref('api/users/{userId}/posts/{postId}');
            const { identifyVariableSegments } = useRouteSegmentSelection({ endpoint });

            // Act

            const result = identifyVariableSegments('api/users/{userId}/posts/{postId}');

            // Assert

            expect(result).toEqual([2, 4]);
        });
    });

    /*
     * Behavior tests.
     */

    describe('Interaction', () => {
        let mockInput: HTMLInputElement;

        beforeEach(() => {
            mockInput = document.createElement('input');
            mockInput.setSelectionRange = vi.fn();
            document.body.appendChild(mockInput);
        });

        it('selects segment with braces when clicked', async () => {
            // Arrange

            const endpoint = ref('api/users/{id}');
            const { handleClick } = useRouteSegmentSelection({ endpoint });
            mockInput.value = 'api/users/{id}';
            mockInput.selectionStart = 12; // Inside {id}

            const event = new MouseEvent('click', { bubbles: true });
            Object.defineProperty(event, 'target', {
                value: mockInput,
                enumerable: true,
            });

            // Act

            handleClick(event);
            await nextTick();

            // Assert

            expect(mockInput.setSelectionRange).toHaveBeenCalledWith(10, 14);
        });
    });
});
