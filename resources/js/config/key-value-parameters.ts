/**
 * Key-value parameters configuration
 *
 * This configuration defines timing settings for parameter manipulation
 * and synchronization to provide smooth user interactions.
 */
export const keyValueParametersConfig = {
    /**
     * Deletion confirmation timeout in milliseconds (1.2 seconds).
     *
     * Time window for users to cancel parameter deletion after clicking delete.
     * Provides safety mechanism to prevent accidental data loss.
     */
    DELETION_CONFIRMATION_TIMEOUT: 1200,
} satisfies KeyValueParametersConfig;

export interface KeyValueParametersConfig {
    DELETION_CONFIRMATION_TIMEOUT: number;
}
