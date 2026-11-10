import { jsonExtensions } from '@/components/domain/CodeEditor/extensions';
import { forEachDiagnostic, forceLinting } from '@codemirror/lint';
import { EditorView } from '@codemirror/view';
import type { JSONSchema7 } from 'json-schema';
import { afterEach, describe, expect, it } from 'vitest';

const schema: JSONSchema7 = {
    type: 'object',
    properties: { type: { type: 'string' }, count: { type: 'integer' } },
    additionalProperties: false,
};

let view: EditorView | null = null;

const lint = async (doc: string): Promise<string[]> => {
    view = new EditorView({
        doc,
        parent: document.body,
        extensions: jsonExtensions(false, schema),
    });

    forceLinting(view);
    await new Promise(resolve => setTimeout(resolve, 800));

    const messages: string[] = [];

    forEachDiagnostic(view.state, diagnostic => messages.push(diagnostic.message));

    return messages;
};

describe('JSON editor linting with dynamic variables', () => {
    afterEach(() => {
        view?.destroy();
        view = null;
    });

    it('reports no error for an unquoted dynamic variable as a value', async () => {
        const messages = await lint(
            '{\n  "type": "created",\n  "count": {$randomInt}\n}',
        );

        expect(messages).toEqual([]);
    });

    it('reports no error for a dynamic variable inside a string', async () => {
        expect(await lint('{ "type": "id-{$uuid}" }')).toEqual([]);
    });

    it('still reports a real syntax error', async () => {
        expect((await lint('{ "type": }')).length).toBeGreaterThan(0);
    });

    it('still reports a real schema error', async () => {
        const messages = await lint('{ "type": "a", "other": {$uuid} }');

        expect(messages.some(message => message.includes('other'))).toBe(true);
    });
});
