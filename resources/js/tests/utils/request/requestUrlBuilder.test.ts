import type { ParameterContract } from '@/interfaces';
import { ParameterType } from '@/interfaces/ui';
import {
    buildRequestUrl,
    isValidQueryParameter,
} from '@/utils/request/requestUrlBuilder';
import { describe, expect, it } from 'vitest';

describe('requestUrlBuilder', () => {
    describe('isValidQueryParameter', () => {
        it('returns true for valid parameters with keys', () => {
            // Arrange

            const param: ParameterContract = {
                key: 'page',
                value: '1',
                enabled: true,
                type: ParameterType.Text,
            };

            // Act

            const result = isValidQueryParameter(param);

            // Assert

            expect(result).toBe(true);
        });

        it('returns false for parameters with empty keys', () => {
            // Arrange

            const param: ParameterContract = {
                key: '  ',
                value: '1',
                enabled: true,
                type: ParameterType.Text,
            };

            // Act

            const result = isValidQueryParameter(param);

            // Assert

            expect(result).toBe(false);
        });
    });

    describe('buildRequestUrl', () => {
        const baseUrl = 'https://api.example.com';
        const endpoint = 'users';

        it('builds basic URL without parameters', () => {
            // Act

            const result = buildRequestUrl(baseUrl, endpoint, []);

            // Assert

            expect(result).toBe('https://api.example.com/users');
        });

        it('builds URL with simple query parameters', () => {
            // Arrange

            const params: ParameterContract[] = [
                {
                    key: 'page',
                    value: '1',
                    enabled: true,
                    type: ParameterType.Text,
                },
                {
                    key: 'limit',
                    value: '10',
                    enabled: true,
                    type: ParameterType.Text,
                },
            ];

            // Act

            const result = buildRequestUrl(baseUrl, endpoint, params);

            // Assert

            expect(result).toBe('https://api.example.com/users?page=1&limit=10');
        });

        it('ignores invalid parameters', () => {
            // Arrange

            const params: ParameterContract[] = [
                {
                    key: '',
                    value: 'val',
                    enabled: true,
                    type: ParameterType.Text,
                },
                {
                    key: 'valid',
                    value: 'ok',
                    enabled: true,
                    type: ParameterType.Text,
                },
            ];

            // Act

            const result = buildRequestUrl(baseUrl, endpoint, params);

            // Assert

            expect(result).toBe('https://api.example.com/users?valid=ok');
        });

        it('resolves parameters via resolver when provided', () => {
            // Arrange

            const params: ParameterContract[] = [
                {
                    key: 'token',
                    value: '{{api_token}}',
                    enabled: true,
                    type: ParameterType.Text,
                },
            ];
            const resolver = (val: string | number | boolean | null | undefined) =>
                String(val).replace('{{api_token}}', 'secret');

            // Act

            const result = buildRequestUrl(baseUrl, endpoint, params, resolver);

            // Assert

            expect(result).toBe('https://api.example.com/users?token=secret');
        });

        it('handles array values in parameters', () => {
            // Arrange

            const params: ParameterContract[] = [
                {
                    type: ParameterType.Text,
                    key: 'tags',
                    value: ['nimbus', 'vitest'] as unknown as string,
                    enabled: true,
                },
            ];

            // Act

            const result = buildRequestUrl(baseUrl, endpoint, params);

            // Assert

            expect(result).toBe(
                'https://api.example.com/users?tags%5B%5D=nimbus&tags%5B%5D=vitest',
            );
        });

        it('handles nested object values in parameters', () => {
            // Arrange

            const params: ParameterContract[] = [
                {
                    type: ParameterType.Text,
                    key: 'filter',
                    value: { status: 'active', sort: 'desc' } as unknown as string,
                    enabled: true,
                },
            ];

            // Act

            const result = buildRequestUrl(baseUrl, endpoint, params);

            // Assert

            expect(result).toBe(
                'https://api.example.com/users?filter%5Bstatus%5D=active&filter%5Bsort%5D=desc',
            );
        });

        it('handles deeply nested complex objects', () => {
            // Arrange

            const params: ParameterContract[] = [
                {
                    type: ParameterType.Text,
                    key: 'a',
                    value: { b: { c: [1, 2] } } as unknown as string,
                    enabled: true,
                },
            ];

            // Act

            const result = buildRequestUrl(baseUrl, endpoint, params);

            // Assert

            expect(result).toBe(
                'https://api.example.com/users?a%5Bb%5D%5Bc%5D%5B%5D=1&a%5Bb%5D%5Bc%5D%5B%5D=2',
            );
        });

        it('includes null and undefined values as empty strings if enabled', () => {
            // Arrange

            const params: ParameterContract[] = [
                // @ts-expect-error testing edge case.
                { type: ParameterType.Text, key: 'nullVal', value: null, enabled: true },
                {
                    type: ParameterType.Text,
                    key: 'undefVal',
                    // @ts-expect-error testing edge case.
                    value: undefined,
                    enabled: true,
                },
                {
                    type: ParameterType.Text,
                    key: 'valid',
                    value: 'ok',
                    enabled: true,
                },
            ];

            // Act

            const result = buildRequestUrl(baseUrl, endpoint, params);

            // Assert

            expect(result).toBe(
                'https://api.example.com/users?nullVal=&undefVal=&valid=ok',
            );
        });
    });
});
