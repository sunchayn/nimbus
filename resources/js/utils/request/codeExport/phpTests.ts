import {
    fieldsObject,
    indentLines,
    php,
    phpHeaders,
    phpString,
    toSnakeCase,
} from './literals';
import type { CodeGenerator, ExportModel } from './types';

const JSON_VERBS = ['get', 'post', 'put', 'patch', 'delete', 'options'];

/**
 * Builds the feature test call that sends the request.
 */
const buildTestCall = (model: ExportModel): string => {
    const verb = model.method.toLowerCase();
    const path = phpString(model.path);
    const body = model.body;
    const start = model.actor
        ? '$this->actingAs(User::factory()->create())\n        '
        : '$this\n        ';
    const withHeaders =
        model.headers.length > 0
            ? `->withHeaders(${indentLines(phpHeaders(model.headers), 8)})\n        `
            : '';
    const prefix = `${start}${withHeaders}`;

    if (body.kind === 'none' && JSON_VERBS.includes(verb)) {
        return `${prefix}->${verb}Json(${path})`;
    }

    if (body.kind === 'json' && JSON_VERBS.includes(verb)) {
        return `${prefix}->${verb}Json(${path}, ${indentLines(php(body.value), 8)})`;
    }

    if (body.kind === 'fields') {
        return `${prefix}->${verb}(${path}, ${indentLines(php(fieldsObject(body.fields)), 8)})`;
    }

    const content = body.kind === 'text' ? phpString(body.value) : "''";

    return `${prefix}->call(${phpString(model.method)}, ${path}, [], [], [], [], ${content})`;
};

const userImport = (model: ExportModel): string =>
    model.actor ? 'use App\\Models\\User;\n' : '';

export const generatePest: CodeGenerator = (model, expectedStatus) => {
    const imports = model.actor ? `${userImport(model)}\n` : '';
    const title = `sends ${model.method} ${model.path.replace(/'/g, "\\'")}`;

    return `${imports}it('${title}', function () {
    $response = ${buildTestCall(model)};

    $response->assertStatus(${expectedStatus});
});
`;
};

export const generatePhpUnit: CodeGenerator = (model, expectedStatus) => {
    const imports = `${userImport(model)}use Tests\\TestCase;\n`;
    const className = `${model.method.charAt(0)}${model.method.slice(1).toLowerCase()}RequestTest`;
    const methodName = `test_${model.method.toLowerCase()}_${toSnakeCase(model.path) || 'root'}`;

    return `namespace Tests\\Feature;\n\n${imports}\nclass ${className} extends TestCase\n{\n    public function ${methodName}(): void\n    {\n        $response = ${indentLines(buildTestCall(model), 4)};\n\n        $response->assertStatus(${expectedStatus});\n    }\n}\n`;
};
