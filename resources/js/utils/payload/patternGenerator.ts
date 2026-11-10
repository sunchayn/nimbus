import { faker } from '@faker-js/faker/locale/en';

interface Node {
    pick: () => string;
}

const CLASS_ESCAPES: Record<string, string> = {
    '\\d': '0123456789',
    '\\w': 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_',
    '\\s': ' ',
    '\\p{L}': 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
    '\\p{Lu}': 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
    '\\p{Ll}': 'abcdefghijklmnopqrstuvwxyz',
    '\\p{N}': '0123456789',
    '\\p{Nd}': '0123456789',
    '\\p{M}': '́̈',
};

const OPEN_ENDED_REPEAT = 5;

function expandRange(from: string, to: string): string {
    let chars = '';

    for (let code = from.charCodeAt(0); code <= to.charCodeAt(0); code++) {
        chars += String.fromCharCode(code);
    }

    return chars;
}

/**
 * Parses a regular expression body into a tree of value pickers.
 * Anything outside the supported syntax throws, so the caller can give up.
 */
class PatternParser {
    private position = 0;

    constructor(private readonly source: string) {}

    parse(): Node {
        const node = this.parseAlternatives();

        if (this.position < this.source.length) {
            throw new Error('Unsupported pattern');
        }

        return node;
    }

    private parseAlternatives(): Node {
        const branches: Node[] = [this.parseSequence()];

        while (this.source[this.position] === '|') {
            this.position++;
            branches.push(this.parseSequence());
        }

        return { pick: () => faker.helpers.arrayElement(branches).pick() };
    }

    private parseSequence(): Node {
        const parts: Node[] = [];

        while (this.position < this.source.length) {
            const char = this.source[this.position];

            if (char === '|' || char === ')') {
                break;
            }

            if (char === '^' || char === '$') {
                this.position++;
                continue;
            }

            parts.push(this.parseQuantified(this.parseAtom()));
        }

        return { pick: () => parts.map(part => part.pick()).join('') };
    }

    private parseAtom(): Node {
        const char = this.source[this.position];

        if (char === '(') {
            return this.parseGroup();
        }

        if (char === '[') {
            return this.parseClass();
        }

        if (char === '.') {
            this.position++;

            return this.fromChars(CLASS_ESCAPES['\\w']);
        }

        if (char === '\\') {
            return this.fromChars(this.parseEscape());
        }

        if ('*+?{}'.includes(char)) {
            throw new Error('Unsupported pattern');
        }

        this.position++;

        return { pick: () => char };
    }

    private parseGroup(): Node {
        this.position++;

        if (this.source.startsWith('?:', this.position)) {
            this.position += 2;
        } else if (this.source[this.position] === '?') {
            throw new Error('Unsupported pattern');
        }

        const node = this.parseAlternatives();

        if (this.source[this.position] !== ')') {
            throw new Error('Unsupported pattern');
        }

        this.position++;

        return node;
    }

    private parseEscape(): string {
        const unicode = /^\\p\{[A-Za-z]+\}/.exec(this.source.slice(this.position));
        const token = unicode
            ? unicode[0]
            : this.source.slice(this.position, this.position + 2);

        this.position += token.length;

        if (token in CLASS_ESCAPES) {
            return CLASS_ESCAPES[token];
        }

        if (/^\\[a-zA-Z0-9]$/.test(token) && !'nrt'.includes(token[1])) {
            throw new Error('Unsupported pattern');
        }

        return { '\\n': '\n', '\\r': '\r', '\\t': '\t' }[token] ?? token[1];
    }

    private parseClass(): Node {
        this.position++;

        if (this.source[this.position] === '^') {
            throw new Error('Unsupported pattern');
        }

        let chars = '';

        while (this.position < this.source.length && this.source[this.position] !== ']') {
            const token = this.readClassMember();

            if (
                this.source[this.position] === '-' &&
                this.source[this.position + 1] !== ']'
            ) {
                this.position++;
                chars += expandRange(token, this.readClassMember());
            } else {
                chars += token;
            }
        }

        if (this.source[this.position] !== ']' || chars === '') {
            throw new Error('Unsupported pattern');
        }

        this.position++;

        return this.fromChars(chars);
    }

    private readClassMember(): string {
        if (this.source[this.position] === '\\') {
            return this.parseEscape();
        }

        return this.source[this.position++];
    }

    private parseQuantified(atom: Node): Node {
        const [min, max] = this.parseQuantifier();

        if (min === 1 && max === 1) {
            return atom;
        }

        return {
            pick: () =>
                Array.from({ length: faker.number.int({ min, max }) }, () =>
                    atom.pick(),
                ).join(''),
        };
    }

    private parseQuantifier(): [number, number] {
        const char = this.source[this.position];

        const simple: Record<string, [number, number]> = {
            '*': [0, OPEN_ENDED_REPEAT],
            '+': [1, OPEN_ENDED_REPEAT],
            '?': [0, 1],
        };

        if (char in simple) {
            this.position++;

            return simple[char];
        }

        const braces = /^\{(\d+)(?:(,)(\d*))?\}/.exec(this.source.slice(this.position));

        if (!braces) {
            return [1, 1];
        }

        this.position += braces[0].length;

        const min = Number(braces[1]);
        const max = braces[2] ? Number(braces[3] || min + OPEN_ENDED_REPEAT) : min;

        return [min, max];
    }

    private fromChars(chars: string): Node {
        const options = Array.from(chars);

        return { pick: () => faker.helpers.arrayElement(options) };
    }
}

/**
 * Builds a string that matches a schema pattern, or returns null when the
 * pattern uses syntax the generator does not understand.
 */
export function generateFromPattern(
    pattern: string,
    maxLength?: number,
    minLength?: number,
): string | null {
    let tree: Node;
    let matcher: RegExp;

    try {
        tree = new PatternParser(pattern).parse();
        matcher = new RegExp(pattern, 'u');
    } catch {
        return null;
    }

    for (let attempt = 0; attempt < 20; attempt++) {
        const value = tree.pick();

        if (
            matcher.test(value) &&
            (maxLength === undefined || value.length <= maxLength) &&
            (minLength === undefined || value.length >= minLength)
        ) {
            return value;
        }
    }

    return null;
}
