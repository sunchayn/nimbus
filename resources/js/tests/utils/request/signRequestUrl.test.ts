import { ParameterType } from '@/interfaces/ui/key-value-parameters';
import { createMockPendingRequest } from '@/tests/_utils/testFactories';
import { signRequestUrl } from '@/utils/request/signRequestUrl';
import axios from 'axios';
import type { Mocked } from 'vitest';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('axios');
const mockedAxios = axios as Mocked<typeof axios>;

const options = {
    apiUrl: 'https://api.example.com',
    appBasePath: '/nimbus',
    resolve: (value: string | number | boolean | null | undefined) => String(value ?? ''),
};

describe('signRequestUrl', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockedAxios.isAxiosError.mockImplementation(
            (error: unknown) =>
                (error as { isAxiosError?: boolean }).isAxiosError === true,
        );
    });

    it('sends the full URL with its query and returns the signed endpoint', async () => {
        // Arrange

        const request = createMockPendingRequest({
            method: 'GET',
            endpoint: 'unsubscribe/5',
            queryParameters: [
                { type: ParameterType.Text, key: 'ref', value: 'mail', enabled: true },
            ],
        });

        mockedAxios.post.mockResolvedValue({
            data: { url: 'https://api.example.com/unsubscribe/5?ref=mail&signature=abc' },
        });

        // Act

        const signed = await signRequestUrl(request, options);

        // Assert

        expect(mockedAxios.post).toHaveBeenCalledWith(
            '/nimbus/api/magic/signed-url',
            { method: 'GET', url: 'https://api.example.com/unsubscribe/5?ref=mail' },
            { signal: undefined },
        );
        expect(signed.endpoint).toBe('unsubscribe/5?ref=mail&signature=abc');
        expect(signed.queryParameters).toEqual([]);
    });

    it('throws the message of a refused signing request', async () => {
        // Arrange

        mockedAxios.post.mockRejectedValue({
            isAxiosError: true,
            response: {
                data: { message: 'The route does not use the signed middleware.' },
            },
        });

        // Act and Assert

        await expect(
            signRequestUrl(createMockPendingRequest({ endpoint: 'plain' }), options),
        ).rejects.toThrow('The route does not use the signed middleware.');
    });
});
