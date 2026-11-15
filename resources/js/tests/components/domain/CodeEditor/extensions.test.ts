import { jsonExtensions } from '@/components/domain/CodeEditor/extensions';
import { CODE_EDITOR_INDENT } from '@/config/code-editor';
import { getIndentation } from '@codemirror/language';
import { EditorState } from '@codemirror/state';
import { describe, expect, it } from 'vitest';

describe('jsonExtensions', () => {
    it('indents a new line like the lines above it', () => {
        // Arrange

        const doc = '{\n  "user_id": 7169,\n';
        const state = EditorState.create({
            doc,
            extensions: jsonExtensions(false, undefined),
        });

        // Act

        const indentation = getIndentation(state, doc.length);

        // Assert

        expect(CODE_EDITOR_INDENT).toBe(2);
        expect(indentation).toBe(2);
    });
});
