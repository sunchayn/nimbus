import { JUMP_HIGHLIGHT_MS, useJumpHighlight } from '@/composables/ui/useJumpHighlight';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

describe('useJumpHighlight', () => {
    const scrollIntoView = vi.fn();

    beforeEach(() => {
        vi.useFakeTimers();
        scrollIntoView.mockClear();
        Element.prototype.scrollIntoView = scrollIntoView;
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('marks the element and scrolls to it', async () => {
        const { highlighted, bind, jump } = useJumpHighlight();

        bind('a')(document.createElement('div'));

        await jump('a');

        expect(highlighted.value).toBe('a');
        expect(scrollIntoView).toHaveBeenCalledOnce();
    });

    it('clears the mark after a moment', async () => {
        const { highlighted, jump } = useJumpHighlight();

        await jump('a');
        await vi.advanceTimersByTimeAsync(JUMP_HIGHLIGHT_MS + 100);

        expect(highlighted.value).toBeNull();
    });

    it('keeps the mark while the moment lasts', async () => {
        const { highlighted, jump } = useJumpHighlight();

        await jump('a');
        await vi.advanceTimersByTimeAsync(JUMP_HIGHLIGHT_MS - 100);

        expect(highlighted.value).toBe('a');
    });

    it('does not scroll to an element that was removed', async () => {
        const { bind, jump } = useJumpHighlight();

        bind('a')(document.createElement('div'));
        bind('a')(null);

        await jump('a');

        expect(scrollIntoView).not.toHaveBeenCalled();
    });
});
