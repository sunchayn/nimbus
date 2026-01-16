import HistoryItem from '@/components/domain/Client/Response/ResponseStatus/History/HistoryItem.vue';
import { AuthorizationType } from '@/interfaces/generated';
import { RequestLog } from '@/interfaces/history/logs';
import { RequestBodyTypeEnum, Response, STATUS } from '@/interfaces/http';
import { renderWithProviders, screen } from '@/tests/_utils/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@/components/base/dropdown-menu', () => ({
    AppDropdownMenuItem: {
        name: 'AppDropdownMenuItem',
        template: '<div><slot /></div>',
    },
}));

describe('HistoryItem', () => {
    const mockLog: RequestLog & { response: Response } = {
        durationInMs: 150,
        isProcessing: false,
        request: {
            method: 'GET',
            endpoint: '/api/test',
            headers: [],
            queryParameters: [],
            body: null,
            payloadType: RequestBodyTypeEnum.EMPTY,
            authorization: { type: AuthorizationType.None },
            routeDefinition: {
                method: 'GET',
                endpoint: '/api/test',
                shortEndpoint: '/api/test',
                schema: { shape: {}, extractionErrors: null },
            },
        },
        response: {
            status: STATUS.SUCCESS,
            statusCode: 200,
            statusText: 'OK',
            timestamp: Math.floor(Date.now() / 1000) - 60, // 1 minute ago
            body: '{}',
            headers: [],
            cookies: [],
            sizeInBytes: 1024,
        },
    };

    it('renders history item details correctly including relative timestamp', () => {
        renderWithProviders(HistoryItem, {
            props: {
                log: mockLog,
                index: 0,
            },
        });

        expect(screen.getByTestId('history-item-method')).toHaveTextContent('GET');
        expect(screen.getByTestId('history-item-endpoint')).toHaveTextContent(
            '/api/test',
        );
        expect(screen.getByTestId('response-status-badge')).toHaveTextContent('200 - OK');

        // Assert relative timestamp
        const timestamp = screen.getByText(
            (content, element) => element?.tagName === 'SMALL',
        );
        expect(timestamp.textContent).toMatch(/1 minute ago|1 min ago/);
    });
});
