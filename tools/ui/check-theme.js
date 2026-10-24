/*
 * Guards the design tokens.
 * It fails when tools/ui/theme.js or a Vue component under resources/js holds a raw palette class,
 * an arbitrary px, rem or hex value, a variable class, or a half step spacing class.
 * Use the named token utilities from resources/css instead.
 */
import { readdirSync, readFileSync } from 'node:fs';
import { join, relative, sep } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('../../', import.meta.url));
const themeFile = join(root, 'tools/ui/theme.js');
const jsRoot = join(root, 'resources/js');

// The logo gradient needs literal hex stops.
const vueAllowlist = ['resources/js/components/base/icons/AppBrandIcon.vue'];

const variableClass = {
    name: 'variable class, use the named token utility',
    pattern: /-\((?:[a-z]+:)?--(?!reka-)/,
};

const paletteClass = {
    name: 'raw palette class',
    pattern:
        /\b(?:bg|text|border|ring|from|via|to|fill|stroke|outline|divide|shadow)-(?:zinc|gray|slate|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose|white|black)\b/,
};

const sharedRules = [paletteClass, variableClass];

const themeRules = [
    ...sharedRules,
    {
        name: 'arbitrary px, rem or hex value',
        pattern: /[[(][^\])]*?(?:\d(?:px|rem)\b|#[0-9a-f]{3,8}\b)/i,
    },
    {
        name: 'theme name',
        pattern: /data-theme|\btheme-[a-z]+:|\b(?:compact|cartoon)\b/,
    },
];

// Only class utilities such as w-[10px] or grid-cols-[1rem_auto]. Plain CSS in style blocks is allowed.
const vueRules = [
    ...sharedRules,
    {
        name: 'arbitrary px, rem or hex value',
        pattern: /[\w\]]-\[[^\]]*?(?:\d(?:px|rem)(?![a-z\d])|#[0-9a-f]{3,8}(?![\w]))/i,
    },
    {
        name: 'half step spacing, use the 4px grid or a spacing token',
        pattern:
            /(?<![\w.-])-?(?:[a-z]+:)*-?(?:[mp][xytrblse]?|gap(?:-[xy])?|space-[xy]|inset(?:-[xy])?|top|right|bottom|left|start|end|size|[hw]|min-[hw]|max-[hw])-\d+\.5(?![\d.])/,
    },
];

const walk = dir =>
    readdirSync(dir, { withFileTypes: true }).flatMap(entry => {
        const path = join(dir, entry.name);
        if (entry.isDirectory()) {
            return ['tests', 'generated'].includes(entry.name) ? [] : walk(path);
        }
        return entry.name.endsWith('.vue') ? [path] : [];
    });

const scan = (file, rules) => {
    const name = relative(root, file).split(sep).join('/');
    return readFileSync(file, 'utf8')
        .split('\n')
        .flatMap((line, index) =>
            rules
                .filter(rule => rule.pattern.test(line))
                .map(rule => `${name}:${index + 1} ${rule.name}\n    ${line.trim()}`),
        );
};

const vueFiles = walk(jsRoot).filter(
    file => !vueAllowlist.includes(relative(root, file).split(sep).join('/')),
);

const problems = [
    ...scan(themeFile, themeRules),
    ...vueFiles.flatMap(file => scan(file, vueRules)),
];

if (problems.length > 0) {
    console.error(problems.join('\n'));
    process.exit(1);
}

console.log(
    `Checked tools/ui/theme.js and ${vueFiles.length} Vue files. No raw palette class, arbitrary value, variable class or half step spacing.`,
);
