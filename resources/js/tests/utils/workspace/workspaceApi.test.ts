import {
    moveWorkspaceRequest,
    renameWorkspaceFolder,
    reorderWorkspaceRequests,
    workspaceErrorMessage,
} from '@/utils/workspace';
import axios, { AxiosError } from 'axios';
import { afterEach, describe, expect, it, vi } from 'vitest';

const failure = (status: number, data: unknown) =>
    new AxiosError('Request failed', 'ERR_BAD_REQUEST', undefined, undefined, {
        status,
        data,
        statusText: '',
        headers: {},
        config: { headers: new axios.AxiosHeaders() },
    });

describe('workspaceErrorMessage', () => {
    it('explains a conflict', () => {
        expect(workspaceErrorMessage(failure(409, {}))).toContain('changed on disk');
    });

    it('uses the first validation error', () => {
        const error = failure(422, {
            message: 'invalid',
            errors: { 'authorization.value': ['Use a variable.'] },
        });

        expect(workspaceErrorMessage(error)).toBe('Use a variable.');
    });

    it('uses the message of the response', () => {
        expect(workspaceErrorMessage(failure(500, { message: 'Server error.' }))).toBe(
            'Server error.',
        );
    });

    it('uses the message of a plain error', () => {
        expect(workspaceErrorMessage(new Error('Offline'))).toBe('Offline');
    });
});

describe('workspace folder and item calls', () => {
    afterEach(() => vi.restoreAllMocks());

    it('saves the order of the requests of a folder', async () => {
        const put = vi.spyOn(axios, 'put').mockResolvedValue({ data: null });

        await reorderWorkspaceRequests('users', ['b', 'a']);

        expect(put).toHaveBeenCalledWith(
            expect.stringContaining('/api/workspace/requests/order'),
            {
                folder: 'users',
                requests: ['b', 'a'],
            },
        );
    });

    it('renames a folder and returns its new path', async () => {
        const patch = vi
            .spyOn(axios, 'patch')
            .mockResolvedValue({ data: { folder: 'people' } });

        const path = await renameWorkspaceFolder('users', 'People');

        expect(path).toBe('people');
        expect(patch).toHaveBeenCalledWith(
            expect.stringContaining('/api/workspace/folders'),
            {
                folder: 'users',
                name: 'People',
            },
        );
    });

    it('moves a request through its location', async () => {
        const put = vi
            .spyOn(axios, 'put')
            .mockResolvedValue({ data: { id: 'request-1234' } });

        await moveWorkspaceRequest('request-1234', { folder: 'users' }, 'a1b2');

        expect(put).toHaveBeenCalledWith(
            expect.stringContaining('/api/workspace/requests/request-1234/location'),
            { folder: 'users', hash: 'a1b2' },
        );
    });
});
