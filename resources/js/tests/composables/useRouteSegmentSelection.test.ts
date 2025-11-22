import { useRouteSegmentSelection } from '@/composables/request/useRouteSegmentSelection';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick, ref } from 'vue';

describe('useRouteSegmentSelection', () => {
    describe('identifyVariableSegments', () => {
        it('identifies single variable segment', () => {
            const endpoint = ref('api/users/{id}');
            const { identifyVariableSegments } = useRouteSegmentSelection({ endpoint });

            const result = identifyVariableSegments('api/users/{id}');

            expect(result).toEqual([2]);
        });

        it('identifies multiple variable segments', () => {
            const endpoint = ref('api/users/{userId}/posts/{postId}');
            const { identifyVariableSegments } = useRouteSegmentSelection({ endpoint });

            const result = identifyVariableSegments('api/users/{userId}/posts/{postId}');

            expect(result).toEqual([2, 4]);
        });

        it('returns empty array when no variable segments exist', () => {
            const endpoint = ref('api/users/list');
            const { identifyVariableSegments } = useRouteSegmentSelection({ endpoint });

            const result = identifyVariableSegments('api/users/list');

            expect(result).toEqual([]);
        });

        it('handles empty string', () => {
            const endpoint = ref('');
            const { identifyVariableSegments } = useRouteSegmentSelection({ endpoint });

            const result = identifyVariableSegments('');

            expect(result).toEqual([]);
        });

        it('ignores partial braces', () => {
            const endpoint = ref('api/{users/posts}');
            const { identifyVariableSegments } = useRouteSegmentSelection({ endpoint });

            const result = identifyVariableSegments('api/{users/posts}');

            expect(result).toEqual([]);
        });
    });

    describe('variableSegmentIndices tracking', () => {
        it('initializes with variable segments from endpoint', () => {
            const endpoint = ref('api/users/{id}/posts/{postId}');
            const { variableSegmentIndices } = useRouteSegmentSelection({ endpoint });

            expect(variableSegmentIndices.value).toEqual([2, 4]);
        });

        it('updates when endpoint changes to include braces', async () => {
            const endpoint = ref('api/users/123');
            const { variableSegmentIndices } = useRouteSegmentSelection({ endpoint });

            expect(variableSegmentIndices.value).toEqual([]);

            endpoint.value = 'api/users/{id}';
            await nextTick();

            expect(variableSegmentIndices.value).toEqual([2]);
        });

        it('does not update when endpoint changes without braces', async () => {
            const endpoint = ref('api/users/{id}');
            const { variableSegmentIndices } = useRouteSegmentSelection({ endpoint });

            expect(variableSegmentIndices.value).toEqual([2]);

            endpoint.value = 'api/users/123';
            await nextTick();

            expect(variableSegmentIndices.value).toEqual([2]); // Should remain unchanged
        });
    });

    describe('handleClick', () => {
        let mockInput: HTMLInputElement;

        beforeEach(() => {
            mockInput = document.createElement('input');
            mockInput.setSelectionRange = vi.fn();
            document.body.appendChild(mockInput);
        });

        it('selects segment with braces when clicked', async () => {
            const endpoint = ref('api/users/{id}');
            const { handleClick } = useRouteSegmentSelection({ endpoint });

            mockInput.value = 'api/users/{id}';
            mockInput.selectionStart = 12; // Inside {id}

            const event = new MouseEvent('click', { bubbles: true });
            Object.defineProperty(event, 'target', { value: mockInput, enumerable: true });

            handleClick(event);
            await nextTick();

            expect(mockInput.setSelectionRange).toHaveBeenCalledWith(10, 14); // <- Selects {id}
        });

        it('selects entire braced segment when clicking at opening brace', async () => {
            const endpoint = ref('api/users/{userId}');
            const { handleClick } = useRouteSegmentSelection({ endpoint });

            mockInput.value = 'api/users/{userId}';
            mockInput.selectionStart = 10; // <- At the {

            const event = new MouseEvent('click', { bubbles: true });
            Object.defineProperty(event, 'target', { value: mockInput, enumerable: true });

            handleClick(event);
            await nextTick();

            expect(mockInput.setSelectionRange).toHaveBeenCalledWith(10, 18);
        });

        it('selects entire braced segment when clicking at closing brace', async () => {
            const endpoint = ref('api/users/{userId}');
            const { handleClick } = useRouteSegmentSelection({ endpoint });

            mockInput.value = 'api/users/{userId}';
            mockInput.selectionStart = 17; // <- At the }

            const event = new MouseEvent('click', { bubbles: true });
            Object.defineProperty(event, 'target', { value: mockInput, enumerable: true });

            handleClick(event);
            await nextTick();

            expect(mockInput.setSelectionRange).toHaveBeenCalledWith(10, 18);
        });

        it('selects replaced variable segment when clicked', async () => {
            const endpoint = ref('api/users/{id}');
            const { handleClick } = useRouteSegmentSelection({ endpoint });

            // User has replaced {id} with 123
            mockInput.value = 'api/users/123';
            mockInput.selectionStart = 11; // <- Inside 123

            const event = new MouseEvent('click', { bubbles: true });
            Object.defineProperty(event, 'target', { value: mockInput, enumerable: true });

            handleClick(event);
            await nextTick();

            expect(mockInput.setSelectionRange).toHaveBeenCalledWith(10, 13); // <- Selects 123
        });

        it('selects multiple replaced variable segments independently', async () => {
            const endpoint = ref('api/users/{userId}/posts/{postId}');
            const { handleClick } = useRouteSegmentSelection({ endpoint });

            mockInput.value = 'api/users/123/posts/456';

            // Click on first replaced segment (123)
            mockInput.selectionStart = 11;
            let event = new MouseEvent('click', { bubbles: true });
            Object.defineProperty(event, 'target', { value: mockInput, enumerable: true });

            handleClick(event);
            await nextTick();

            expect(mockInput.setSelectionRange).toHaveBeenCalledWith(10, 13);

            // Click on second replaced segment (456)
            mockInput.selectionStart = 21;
            event = new MouseEvent('click', { bubbles: true });
            Object.defineProperty(event, 'target', { value: mockInput, enumerable: true });

            handleClick(event);
            await nextTick();

            expect(mockInput.setSelectionRange).toHaveBeenCalledWith(20, 23);
        });

        it('does not select when clicking on non-variable segment', async () => {
            const endpoint = ref('api/users/{id}');
            const { handleClick } = useRouteSegmentSelection({ endpoint });

            mockInput.value = 'api/users/{id}';
            mockInput.selectionStart = 4; // <- Inside "users"

            const event = new MouseEvent('click', { bubbles: true });
            Object.defineProperty(event, 'target', { value: mockInput, enumerable: true });

            handleClick(event);
            await nextTick();

            expect(mockInput.setSelectionRange).not.toHaveBeenCalled();
        });

        it('prioritizes braced segments over original variable segments', async () => {
            const endpoint = ref('api/users/{id}');
            const { handleClick } = useRouteSegmentSelection({ endpoint });

            // User added braces back after replacing
            mockInput.value = 'api/users/{newId}';
            mockInput.selectionStart = 12; // <- Inside {newId}

            const event = new MouseEvent('click', { bubbles: true });
            Object.defineProperty(event, 'target', { value: mockInput, enumerable: true });

            handleClick(event);
            await nextTick();

            expect(mockInput.setSelectionRange).toHaveBeenCalledWith(10, 17); // Selects {newId}
        });

        it('handles clicking at start of segment', async () => {
            const endpoint = ref('api/users/{id}');
            const { handleClick } = useRouteSegmentSelection({ endpoint });

            mockInput.value = 'api/users/123';
            mockInput.selectionStart = 10; // <- At the start of 123

            const event = new MouseEvent('click', { bubbles: true });
            Object.defineProperty(event, 'target', { value: mockInput, enumerable: true });

            handleClick(event);
            await nextTick();

            expect(mockInput.setSelectionRange).toHaveBeenCalledWith(10, 13);
        });

        it('handles clicking at end of segment', async () => {
            const endpoint = ref('api/users/{id}');
            const { handleClick } = useRouteSegmentSelection({ endpoint });

            mockInput.value = 'api/users/123';
            mockInput.selectionStart = 13; // <- At end of 123

            const event = new MouseEvent('click', { bubbles: true });
            Object.defineProperty(event, 'target', { value: mockInput, enumerable: true });

            handleClick(event);
            await nextTick();

            expect(mockInput.setSelectionRange).toHaveBeenCalledWith(10, 13);
        });

        it('handles empty segments gracefully', async () => {
            const endpoint = ref('api/users/{}');
            const { handleClick } = useRouteSegmentSelection({ endpoint });

            mockInput.value = 'api/users/{}';
            mockInput.selectionStart = 11; // <- Inside {}

            const event = new MouseEvent('click', { bubbles: true });
            Object.defineProperty(event, 'target', { value: mockInput, enumerable: true });

            handleClick(event);
            await nextTick();

            expect(mockInput.setSelectionRange).toHaveBeenCalledWith(10, 12);
        });

        it('does not error when event target is null', async () => {
            const endpoint = ref('api/users/{id}');
            const { handleClick } = useRouteSegmentSelection({ endpoint });

            const event = new MouseEvent('click', { bubbles: true });

            expect(() => handleClick(event)).not.toThrow();
        });

        it('does not error when selectionStart is null', async () => {
            const endpoint = ref('api/users/{id}');
            const { handleClick } = useRouteSegmentSelection({ endpoint });

            const mockInputWithoutSelection = document.createElement('input');
            Object.defineProperty(mockInputWithoutSelection, 'selectionStart', {
                value: null,
                enumerable: true
            });

            const event = new MouseEvent('click', { bubbles: true });
            Object.defineProperty(event, 'target', {
                value: mockInputWithoutSelection,
                enumerable: true
            });

            expect(() => handleClick(event)).not.toThrow();
        });
    });
});
