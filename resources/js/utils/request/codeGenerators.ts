import type { ResolverFn } from '@/interfaces/common/env-vars';
import type { PendingRequest } from '@/interfaces/http';
import { buildExportModel } from './codeExport/exportModel';
import { generateAxios, generateFetch } from './codeExport/javascript';
import { generateGuzzle, generateLaravelHttp } from './codeExport/php';
import { generatePest, generatePhpUnit } from './codeExport/phpTests';
import type { CodeGenerator } from './codeExport/types';

export type CodeLanguage =
    | 'fetch'
    | 'axios'
    | 'laravel-http'
    | 'guzzle'
    | 'pest'
    | 'phpunit';

export const CODE_LANGUAGES: { value: CodeLanguage; label: string }[] = [
    { value: 'fetch', label: 'JavaScript (fetch)' },
    { value: 'axios', label: 'JavaScript (Axios)' },
    { value: 'laravel-http', label: 'PHP (Laravel Http)' },
    { value: 'guzzle', label: 'PHP (Guzzle)' },
    { value: 'pest', label: 'PHP (Pest test)' },
    { value: 'phpunit', label: 'PHP (PHPUnit test)' },
];

const GENERATORS: Record<CodeLanguage, CodeGenerator> = {
    fetch: generateFetch,
    axios: generateAxios,
    'laravel-http': generateLaravelHttp,
    guzzle: generateGuzzle,
    pest: generatePest,
    phpunit: generatePhpUnit,
};

/**
 * Generates a code sample for a request.
 * Variables are resolved and secret values become placeholders.
 */
export function generateCode(
    language: CodeLanguage,
    request: PendingRequest,
    baseUrl: string,
    resolver: ResolverFn,
    expectedStatus = 200,
): string {
    const model = buildExportModel(request, baseUrl, resolver);

    return GENERATORS[language](model, expectedStatus);
}
