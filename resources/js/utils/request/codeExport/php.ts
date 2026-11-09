import { fieldsObject, indentLines, php, phpHeaders, phpString } from './literals';
import type { CodeGenerator, ExportModel, Field } from './types';

const filePath = (name: string): string => `/path/to/${name}`;

const buildMultipartParts = (fields: Field[]): string => {
    const parts = fields.map(field => {
        const contents = field.file
            ? `file_get_contents('${filePath(field.value)}')`
            : phpString(field.value);

        return `        ['name' => ${phpString(field.key)}, 'contents' => ${contents}],`;
    });

    return `[\n${parts.join('\n')}\n    ]`;
};

/**
 * Describes the Laravel Http chain, which is the calls before the request and the request itself.
 */
const buildLaravelHttpCalls = (model: ExportModel): string[] => {
    const verb = model.method.toLowerCase();
    const url = phpString(model.url);
    const body = model.body;
    const headers =
        model.headers.length > 0
            ? [`->withHeaders(${indentLines(phpHeaders(model.headers), 4)})`]
            : [];
    const send = `->send(${phpString(model.method)}, ${url})`;

    switch (body.kind) {
        case 'json':
            return [...headers, `->${verb}(${url}, ${indentLines(php(body.value), 4)})`];
        case 'text':
            return [...headers, `->withBody(${phpString(body.value)})`, send];
        case 'file':
            return [
                ...headers,
                `->withBody(file_get_contents('${filePath(body.name)}'))`,
                send,
            ];
        case 'fields':
            return body.urlencoded
                ? [
                      ...headers,
                      '->asForm()',
                      `->${verb}(${url}, ${indentLines(php(fieldsObject(body.fields)), 4)})`,
                  ]
                : [
                      ...headers,
                      '->asMultipart()',
                      `->${verb}(${url}, ${buildMultipartParts(body.fields)})`,
                  ];
        default:
            return [...headers, `->${verb}(${url})`];
    }
};

export const generateLaravelHttp: CodeGenerator = model => {
    const calls = buildLaravelHttpCalls(model).map((call, index) =>
        index === 0 ? `Http::${call.slice(2)}` : call,
    );

    return `use Illuminate\\Support\\Facades\\Http;\n\n$response = ${calls.join('\n    ')};\n\n$data = $response->json();\n`;
};

const buildGuzzleOptions = (model: ExportModel): string[] => {
    const body = model.body;
    const headers =
        model.headers.length > 0
            ? [`    'headers' => ${indentLines(phpHeaders(model.headers), 4)},`]
            : [];

    switch (body.kind) {
        case 'json':
            return [...headers, `    'json' => ${indentLines(php(body.value), 4)},`];
        case 'text':
            return [...headers, `    'body' => ${phpString(body.value)},`];
        case 'file':
            return [...headers, `    'body' => fopen('${filePath(body.name)}', 'r'),`];
        case 'fields':
            return body.urlencoded
                ? [
                      ...headers,
                      `    'form_params' => ${indentLines(php(fieldsObject(body.fields)), 4)},`,
                  ]
                : [
                      ...headers,
                      `    'multipart' => [\n${body.fields
                          .map(
                              field =>
                                  `        ['name' => ${phpString(field.key)}, 'contents' => ${field.file ? `fopen('${filePath(field.value)}', 'r')` : phpString(field.value)}],`,
                          )
                          .join('\n')}\n    ],`,
                  ];
        default:
            return headers;
    }
};

export const generateGuzzle: CodeGenerator = model => {
    const options = buildGuzzleOptions(model);
    const optionsCode = options.length > 0 ? `, [\n${options.join('\n')}\n]` : '';

    return `$client = new \\GuzzleHttp\\Client();\n\n$response = $client->request(${phpString(model.method)}, ${phpString(model.url)}${optionsCode});\n\n$data = json_decode((string) $response->getBody(), true);\n`;
};
