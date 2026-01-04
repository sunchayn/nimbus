/*
 * This file is auto-generated.
 * Don't update it manually, otherwise, your changes will be lost.
 * To update the file run `php bin/intellisense`.
 *
 * Generated at: 2026-01-02T00:36:13+00:00.
 */

export enum AuthorizationType {
    None = 'none',
    CurrentUser = 'current-user',
    Bearer = 'bearer',
    Basic = 'basic',
    Impersonate = 'impersonate',
}

/**
 * Individual authorization type item
 */
export type AuthorizationTypeItem = {
    readonly id: AuthorizationType;
    readonly label: string;
};
