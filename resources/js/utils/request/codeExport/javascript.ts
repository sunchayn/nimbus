import { fieldsObject, indentLines, js, jsObject } from './literals';
import type { CodeGenerator, ExportBody, ExportModel, Field } from './types';

/**
 * The body of an HTTP client call, with the code that has to come before the call.
 */
interface JsBody {
    preamble: string;
    option: string | null;
}

const NO_BODY: JsBody = { preamble: '', option: null };

const buildFormDataPreamble = (fields: Field[]): string => {
    const appends = fields.map(
        field =>
            `formData.append(${js(field.key)}, ${field.file ? `file /* ${field.value} */` : js(field.value)});`,
    );

    return `const formData = new FormData();\n${appends.join('\n')}\n\n`;
};

const buildUrlSearchParams = (fields: Field[]): string =>
    `new URLSearchParams(${indentLines(js(fieldsObject(fields)), 4)})`;

/**
 * Describes the body for an HTTP client that names its body option.
 * Fetch sends JSON as a string while Axios serializes it.
 */
const buildJsBody = (
    body: ExportBody,
    optionName: 'body' | 'data',
    serializeJson: (value: unknown) => string,
): JsBody => {
    switch (body.kind) {
        case 'json':
            return {
                preamble: '',
                option: `${optionName}: ${serializeJson(body.value)},`,
            };
        case 'text':
            return { preamble: '', option: `${optionName}: ${js(body.value)},` };
        case 'file':
            return {
                preamble: '',
                option: `${optionName}: file, // a File or Blob for ${body.name}`,
            };
        case 'fields':
            return body.urlencoded
                ? {
                      preamble: '',
                      option: `${optionName}: ${buildUrlSearchParams(body.fields)},`,
                  }
                : {
                      preamble: buildFormDataPreamble(body.fields),
                      option: `${optionName}: formData,`,
                  };
        default:
            return NO_BODY;
    }
};

const buildHeadersOption = (model: ExportModel): string[] =>
    model.headers.length > 0
        ? [`headers: ${indentLines(jsObject(model.headers), 4)},`]
        : [];

const toOptionLines = (options: string[]): string =>
    options.map(option => `    ${option}`).join('\n');

export const generateFetch: CodeGenerator = model => {
    const { preamble, option } = buildJsBody(
        model.body,
        'body',
        value => `JSON.stringify(${indentLines(js(value), 4)})`,
    );
    const options = [
        `method: ${js(model.method)},`,
        ...buildHeadersOption(model),
        ...(option ? [option] : []),
    ];

    return `${preamble}const response = await fetch(${js(model.url)}, {\n${toOptionLines(options)}\n});\n\nconst data = await response.json();\n`;
};

export const generateAxios: CodeGenerator = model => {
    const { preamble, option } = buildJsBody(model.body, 'data', value =>
        indentLines(js(value), 4),
    );
    const options = [
        `method: ${js(model.method.toLowerCase())},`,
        `url: ${js(model.url)},`,
        ...buildHeadersOption(model),
        ...(option ? [option] : []),
    ];

    return `import axios from 'axios';\n\n${preamble}const response = await axios.request({\n${toOptionLines(options)}\n});\n\nconst data = response.data;\n`;
};
