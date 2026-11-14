export type SqlTokenType =
    | 'keyword'
    | 'string'
    | 'number'
    | 'comment'
    | 'identifier'
    | 'text';

export interface SqlToken {
    type: SqlTokenType;
    text: string;
}

const KEYWORDS = new Set(
    (
        'select insert into update delete from where and or not in is null like between ' +
        'join inner left right outer full cross on as group by order having limit offset ' +
        'union all distinct values set create alter drop table index exists case when then ' +
        'else end asc desc count sum avg min max returning with begin commit rollback ' +
        'savepoint release default primary key references'
    ).split(' '),
);

// Alternatives are tried in order, so comments and strings win over words and numbers.
const TOKEN_PATTERN =
    /(--[^\n]*|\/\*[\s\S]*?\*\/)|('(?:[^']|'')*'?)|(`[^`]*`?|"[^"]*"?|\[[^\]]*\])|(\b\d+(?:\.\d+)?\b)|([A-Za-z_][A-Za-z0-9_]*)|([\s\S])/g;

/**
 * Splits SQL into tokens that can be colored.
 * The text of all tokens joined together is always the original SQL.
 */
export const tokenizeSql = (sql: string): SqlToken[] => {
    const tokens: SqlToken[] = [];

    const push = (type: SqlTokenType, text: string) => {
        const last = tokens[tokens.length - 1];

        if (last && last.type === type && type === 'text') {
            last.text += text;

            return;
        }

        tokens.push({ type, text });
    };

    for (const match of sql.matchAll(TOKEN_PATTERN)) {
        const [text, comment, string, identifier, number, word] = match;

        if (comment !== undefined) {
            push('comment', text);
        } else if (string !== undefined) {
            push('string', text);
        } else if (identifier !== undefined) {
            push('identifier', text);
        } else if (number !== undefined) {
            push('number', text);
        } else if (word !== undefined) {
            push(KEYWORDS.has(word.toLowerCase()) ? 'keyword' : 'text', text);
        } else {
            push('text', text);
        }
    }

    return tokens;
};
