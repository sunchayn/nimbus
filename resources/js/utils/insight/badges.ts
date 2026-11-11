export type InsightBadgeColor = 'success' | 'info' | 'warning' | 'error' | 'neutral';

/**
 * The badge color of an HTTP call, by the class of its status.
 * A call without a status never got a response, so it counts as an error.
 */
export const httpStatusColor = (status: number | null): InsightBadgeColor => {
    if (status === null || status >= 500) {
        return 'error';
    }

    if (status >= 400) {
        return 'warning';
    }

    if (status >= 300) {
        return 'info';
    }

    return status >= 200 ? 'success' : 'neutral';
};
