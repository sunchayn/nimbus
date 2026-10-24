import { AuthorizationType } from '@/interfaces/generated';
import { RequestBodyTypeEnum } from '@/interfaces/http';
import { ParameterType, type ParameterContract } from '@/interfaces/ui';
import {
    cloneParameters,
    createDefaultAuthorization,
    restoreResponseBody,
} from '@/utils/request/pendingRequestBuilders';
import { describe, expect, it } from 'vitest';

describe('cloneParameters', () => {
    it('returns copies that do not share references', () => {
        // Arrange

        const original: ParameterContract[] = [
            { key: 'a', value: '1', type: ParameterType.Text, enabled: true },
        ];

        // Act

        const copy = cloneParameters(original);
        copy[0].value = '2';

        // Assert

        expect(original[0].value).toBe('1');
    });
});

describe('restoreResponseBody', () => {
    it('sets the content in the method and type slot without mutating the input', () => {
        // Arrange

        const current = { GET: { [RequestBodyTypeEnum.JSON]: 'old' } };

        // Act

        const result = restoreResponseBody(
            current,
            'POST',
            RequestBodyTypeEnum.JSON,
            '{"a":1}',
        );

        // Assert

        expect(result.POST?.[RequestBodyTypeEnum.JSON]).toBe('{"a":1}');
        expect(result.GET?.[RequestBodyTypeEnum.JSON]).toBe('old');
        expect(current).not.toHaveProperty('POST');
    });

    it('keeps other types stored for the same method', () => {
        // Arrange

        const current = { POST: { [RequestBodyTypeEnum.JSON]: 'json' } };

        // Act

        const result = restoreResponseBody(
            current,
            'POST',
            RequestBodyTypeEnum.PLAIN_TEXT,
            'text',
        );

        // Assert

        expect(result.POST?.[RequestBodyTypeEnum.JSON]).toBe('json');
        expect(result.POST?.[RequestBodyTypeEnum.PLAIN_TEXT]).toBe('text');
    });
});

describe('createDefaultAuthorization', () => {
    it.each([
        [AuthorizationType.CurrentUser, { type: AuthorizationType.CurrentUser }],
        [
            AuthorizationType.Impersonate,
            { type: AuthorizationType.Impersonate, value: 1 },
        ],
        [AuthorizationType.Bearer, { type: AuthorizationType.Bearer, value: '' }],
        [
            AuthorizationType.Basic,
            { type: AuthorizationType.Basic, value: { username: '', password: '' } },
        ],
        [AuthorizationType.None, { type: AuthorizationType.None }],
    ])('builds the default for type %s', (type, expected) => {
        expect(createDefaultAuthorization(type)).toEqual(expected);
    });
});
