/**
 * Utility functions organized by domain
 */

export { convertPayloadToFormData, getStatusGroup, normalizeHeaders } from './http';

export { generateRandomPayload, serializeSchemaPayload } from './payload';

export {
    buildRequestUrl,
    createRequestTimer,
    generateCurlCommand,
    generateErrorRequestLog,
    generateSuccessRequestLog,
    getDefaultPayloadTypeForRoute,
} from './request';

export {
    calculateTotalRouteCount,
    parseRouteExtractionException,
    processRoutesData,
    searchRoutes,
} from './routes';

export { calculateScrollToElement, getScrollBounds } from './scroll';

export { cn } from './ui';

export { clearPersistentKeys, uniquePersistenceKey } from './stores';
