import { useToolRequest } from '@/composables/toolbox/useToolRequest';
import type * as Axios from 'axios';
import axios from 'axios';
import { createPinia, setActivePinia } from 'pinia';
import type { Mocked } from 'vitest';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('axios', async importOriginal => {
    const actual = await importOriginal<typeof Axios>();

    return {
        default: {
            ...actual.default,
            post: vi.fn(),
            isAxiosError: actual.default.isAxiosError,
        },
    };
});

const mockedAxios = axios as Mocked<typeof axios>;

describe('useToolRequest', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('posts the payload and keeps the result', async () => {
        // Arrange

        mockedAxios.post.mockResolvedValue({ data: { value: 'plain' } });

        const tool = useToolRequest('decrypt');

        // Act

        await tool.run({ value: 'x' });

        // Assert

        expect(mockedAxios.post).toHaveBeenCalledWith('/api/toolbox/decrypt', {
            value: 'x',
        });
        expect(tool.result.value).toEqual({ value: 'plain' });
        expect(tool.error.value).toBeNull();
        expect(tool.loading.value).toBe(false);
    });

    it('keeps the message of a failed request', async () => {
        // Arrange

        mockedAxios.post.mockRejectedValue(
            Object.assign(new Error('failed'), {
                isAxiosError: true,
                response: { data: { message: 'Cannot decrypt.' } },
            }),
        );

        const tool = useToolRequest('decrypt');

        // Act

        await tool.run({ value: 'x' });

        // Assert

        expect(tool.error.value).toBe('Cannot decrypt.');
        expect(tool.result.value).toBeNull();
    });
});
