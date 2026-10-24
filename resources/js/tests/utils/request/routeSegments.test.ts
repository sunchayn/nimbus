import {
    findBraceSegment,
    findOriginalVariableSegment,
    findSegmentIndexAtCursor,
    getSegmentPosition,
    identifyVariableSegments,
} from '@/utils/request/routeSegments';
import { describe, expect, it } from 'vitest';

describe('routeSegments', () => {
    describe('identifyVariableSegments', () => {
        it('returns the indices of single brace segments only', () => {
            // Act

            const result = identifyVariableSegments(
                'api/users/{id}/posts/{{host}}/{slug}',
            );

            // Assert

            expect(result).toEqual([2, 5]);
        });

        it('returns an empty array when there are no variables', () => {
            expect(identifyVariableSegments('api/users')).toEqual([]);
        });
    });

    describe('findBraceSegment', () => {
        const text = 'api/users/{id}/posts';

        it('returns the range of the brace segment under the cursor', () => {
            expect(findBraceSegment(text, 12)).toEqual({ start: 10, end: 14 });
        });

        it('returns null when the cursor is outside braces', () => {
            expect(findBraceSegment(text, 3)).toBeNull();
        });

        it('ignores environment variables with double braces', () => {
            expect(findBraceSegment('{{host}}/users', 4)).toBeNull();
        });

        it('returns null for an unclosed brace', () => {
            expect(findBraceSegment('api/{id', 6)).toBeNull();
        });
    });

    describe('findSegmentIndexAtCursor', () => {
        it('returns the segment containing the cursor', () => {
            expect(findSegmentIndexAtCursor('api/users/42', 6)).toBe(1);
            expect(findSegmentIndexAtCursor('api/users/42', 11)).toBe(2);
        });

        it('returns null when the cursor is past the end', () => {
            expect(findSegmentIndexAtCursor('api/users', 50)).toBeNull();
        });
    });

    describe('getSegmentPosition', () => {
        it('returns the start and end of a segment', () => {
            expect(getSegmentPosition('api/users/42', 2)).toEqual({ start: 10, end: 12 });
        });

        it('returns null for an index beyond the segments', () => {
            expect(getSegmentPosition('api/users', 5)).toBeNull();
        });
    });

    describe('findOriginalVariableSegment', () => {
        it('returns the segment when it was originally a variable', () => {
            expect(findOriginalVariableSegment('api/users/42', 11, [2])).toEqual({
                start: 10,
                end: 12,
            });
        });

        it('returns null when the segment was not a variable', () => {
            expect(findOriginalVariableSegment('api/users/42', 6, [2])).toBeNull();
        });
    });
});
