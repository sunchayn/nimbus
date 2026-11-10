export interface Field {
    key: string;
    value: string;
    file?: boolean;
}

export interface Header {
    key: string;
    value: string;
}

export type ExportBody =
    | { kind: 'none' }
    | { kind: 'json'; value: unknown }
    | { kind: 'text'; value: string }
    | { kind: 'fields'; fields: Field[]; urlencoded: boolean }
    | { kind: 'file'; name: string };

/**
 * Everything a generator needs to write a request as code.
 */
export interface ExportModel {
    method: string;
    url: string;
    // The path and query without the origin, as a feature test needs it.
    path: string;
    headers: Header[];
    body: ExportBody;
    // A user the request acts as. It becomes actingAs in a test.
    actor: boolean;
}

/**
 * A generator turns the model into source code.
 */
export type CodeGenerator = (model: ExportModel, expectedStatus: number) => string;
