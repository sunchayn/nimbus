import { STATUS } from '@/interfaces/http';

/**
 * Categorizes HTTP status codes into semantic groups.
 *
 * Maps numeric status codes to meaningful categories for UI display
 * and error handling logic.
 */
export const getStatusGroup = (statusCode: number): STATUS => {
    if (statusCode >= 100 && statusCode < 200) {
        return STATUS.INFORMATION;
    }

    if (statusCode >= 200 && statusCode < 300) {
        return STATUS.SUCCESS;
    }

    if (statusCode >= 300 && statusCode < 400) {
        return STATUS.REDIRECT;
    }

    if (statusCode >= 400 && statusCode < 500) {
        return STATUS.CLIENT_ERROR;
    }

    if (statusCode >= 500 && statusCode < 600) {
        return STATUS.SERVER_ERROR;
    }

    if (statusCode === 999) {
        return STATUS.DUMP_AND_DIE;
    }

    return STATUS.OTHER;
};
