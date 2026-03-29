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

        it('does not identify environment variables as variable segments', () => {
            // Arrange

            const endpoint = ref('api/users/{{userId}}/posts/{postId}');
            const { identifyVariableSegments } = useRouteSegmentSelection({ endpoint });

            // Act

            const result = identifyVariableSegments(
                'api/users/{{userId}}/posts/{postId}',
            );

            // Assert

            expect(result).toEqual([4]);
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

        /**
         * Simulates a click on the mock input.
         */
        const simulateClick = (
            input: HTMLInputElement,
            cursorPos: number,
        ): MouseEvent => {
            input.selectionStart = cursorPos;

            const event = new MouseEvent('click', { bubbles: true });
            Object.defineProperty(event, 'target', {
                value: input,
                enumerable: true,
            });

            return event;
        };

        it('selects segment with braces when clicked', async () => {
            // Arrange

            const endpoint = ref('api/users/{id}');
            const { handleClick } = useRouteSegmentSelection({ endpoint });
            mockInput.value = 'api/users/{id}';

            // Act

            handleClick(simulateClick(mockInput, 12));
            await nextTick();

            // Assert

            expect(mockInput.setSelectionRange).toHaveBeenCalledWith(10, 14);
        });

        it('does not select segment when it is an environment variable (double braces)', async () => {
            // Arrange

            const endpoint = ref('api/users/{{id}}');
            const { handleClick } = useRouteSegmentSelection({ endpoint });
            mockInput.value = 'api/users/{{id}}';

            // Act

            handleClick(simulateClick(mockInput, 13));
            await nextTick();

            // Assert

            expect(mockInput.setSelectionRange).not.toHaveBeenCalled();
        });

        it('selects a segment that was originally a variable even if braces are gone', async () => {
            // Arrange

            const endpoint = ref('api/users/{id}');
            const { handleClick, variableSegmentIndices } = useRouteSegmentSelection({
                endpoint,
            });

            // Initialize variable segments
            await nextTick();
            expect(variableSegmentIndices.value).toEqual([2]);

            // Update input to have a value instead of a placeholder
            mockInput.value = 'api/users/123';

            // Act

            handleClick(simulateClick(mockInput, 11)); // Inside '123'
            await nextTick();

            // Assert

            expect(mockInput.setSelectionRange).toHaveBeenCalledWith(10, 13);
        });
    });
});
