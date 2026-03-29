import type { AuthorizationContract } from '@/interfaces/auth/authorization';
import type { RouteDefinition } from '@/interfaces/routes/routes';
import type { ParameterContract } from '@/interfaces/ui';
import type { JSONSchema7 } from 'json-schema';

export enum RequestBodyTypeEnum {
    EMPTY = 'empty',
    JSON = 'json',
    PLAIN_TEXT = 'plain-text',
    FORM_DATA = 'form-data',
}

/**
 * Represents a request that is being built and can be executed.
 *
 * Contains all the necessary data to construct and send an HTTP request.
 */
export interface PendingRequest {
    /** HTTP method for the request (GET, POST, PUT, etc.) */
    method: string;

    /** API endpoint URL path */
    endpoint: string;

    /** HTTP headers to include with the request */
    headers: ParameterContract[];

    /**
     * Request body data organized by HTTP method and payload type.
     *
     * Structure: { [method]: { [payloadType]: bodyData } }
     *
     * This allows storing different body content for different HTTP methods
     * and payload types (JSON, FormData, etc.) for the same request,
     * enabling users to switch between methods while preserving their input.
     *
     * Example: {
     *   "POST": {
     *     "json": '{"name": "John"}',
     *     "form-data": FormData object
     *   },
     *   "PUT": {
     *     "json": '{"id": 1, "name": "Jane"}'
     *   }
     * }
     */
    body: {
        [_key in string]?: {
            [_key in RequestBodyTypeEnum]?: FormData | string | null;
        };
    };

    /** Query parameters to append to the request URL */
    queryParameters: ParameterContract[];

    /** Currently selected payload type for the request body */
    payloadType: RequestBodyTypeEnum;

    /**
     * Schema information for the current request.
     *
     * Contains the shape definition for request validation and
     * any errors encountered during schema extraction from routes.
     */
    schema: {
        shape: JSONSchema7;
        extractionErrors: string | null;
    };

    /** Authorization configuration for the request */
    authorization: AuthorizationContract;

    /**
     * All available routes for the current endpoint (different HTTP methods).
     *
     * Used to:
     * - Categorize methods as "Supported" vs "Other" in the UI
     * - Provide schema information when switching between HTTP methods
     * - Enable method switching while preserving endpoint context
     *
     * Example: For endpoint "/users", this might contain:
     * - GET /users (list users)
     * - POST /users (create user)
     * - PUT /users (update user)
     */
    supportedRoutes: RouteDefinition[];

    /**
     * The currently selected route definition.
     *
     * Represents the specific route the user clicked on or selected,
     * which serves as the base for the current request configuration.
     * This is the "active" route that determines the initial method,
     * endpoint, and schema for the request.
     */
    routeDefinition: RouteDefinition;

    /** Whether the request is currently being executed */
    isProcessing?: boolean;

    /** Request execution duration in milliseconds */
    durationInMs?: number;

    /** Whether the request was executed at least once */
    wasExecuted?: boolean;

    /** Whether to execute the request in transaction mode (rollback on completion) */
    transactionMode?: boolean;
}

export interface Request {
    method: string;
    endpoint: string;
    headers: ParameterContract[];
    body: FormData | string | null;
    queryParameters: ParameterContract[];
    payloadType: RequestBodyTypeEnum;
    authorization: AuthorizationContract;
    routeDefinition: RouteDefinition;
    transactionMode?: boolean;
}
