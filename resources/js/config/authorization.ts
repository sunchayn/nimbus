import type { AuthorizationTypeItem } from '@/interfaces/generated';
import { AuthorizationType } from '@/interfaces/generated';

/**
 * Authorization configuration
 *
 * This configuration defines the available authorization types and their
 * categorization for the API request authorization system.
 */
export const authorizationConfig = {
    TYPES: {
        /**
         * Special authorization types for Nimbus that provide enhanced functionality
         * beyond traditional HTTP authentication methods.
         */
        SPECIAL: [
            {
                id: AuthorizationType.CurrentUser,
                label: 'Currently Logged-in User',
            },
            {
                id: AuthorizationType.Impersonate,
                label: 'Impersonate User with ID',
            },
        ],

        /**
         * Traditional HTTP authorization methods following standard protocols
         * like Bearer tokens and Basic authentication.
         */
        TRADITIONAL: [
            {
                id: AuthorizationType.None,
                label: 'None',
            },
            {
                id: AuthorizationType.Bearer,
                label: 'Bearer Token',
            },
            {
                id: AuthorizationType.Basic,
                label: 'Basic Auth',
            },
        ],
    },

    /**
     * Default authorization type selected when no previous selection exists.
     * Uses CurrentUser as the most common authentication method.
     */
    DEFAULT_TYPE: AuthorizationType.CurrentUser,

    /**
     * Local storage key used to persist user's authorization preferences
     * across browser sessions for improved user experience.
     */
    STORAGE_KEY: 'nimbus-auth-preferences',
} satisfies AuthorizationConfig;

export interface AuthorizationConfig {
    TYPES: {
        SPECIAL: AuthorizationTypeItem[];
        TRADITIONAL: AuthorizationTypeItem[];
    };
    DEFAULT_TYPE: AuthorizationType;
    STORAGE_KEY: string;
}
