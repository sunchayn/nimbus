import RequestBuilder from '@/components/domain/Client/Request/RequestBuilder.vue';
import { renderWithProviders, screen } from '@/tests/_utils/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@/components/domain/Client/Request', () => ({
    RequestBuilderEndpoint: {
        name: 'RequestBuilderEndpoint',
        template: '<div data-testid="request-builder-endpoint">Endpoint</div>',
    },
    RequestParameters: {
        name: 'RequestParameters',
        template: '<div data-testid="request-parameters">Parameters Panel</div>',
    },
    RequestBody: {
        name: 'RequestBody',
        template: '<div data-testid="request-body">Body Panel</div>',
    },
    RequestAuthorization: {
        name: 'RequestAuthorization',
        template: '<div data-testid="request-authorization">Authorization Panel</div>',
    },
    RequestHeaders: {
        name: 'RequestHeaders',
        template: '<div data-testid="request-headers">Headers Panel</div>',
    },
}));

describe('RequestBuilder', () => {
    it('renders endpoint selector and body tab by default', () => {
        renderWithProviders(RequestBuilder);

        expect(screen.getByTestId('request-builder-endpoint')).toBeInTheDocument();
        expect(screen.getByTestId('request-body')).toBeVisible();
    });

    it('switches between panels when different tabs are selected', async () => {
        const { user } = renderWithProviders(RequestBuilder);

        await user.click(screen.getByRole('tab', { name: 'Parameters' }));

        expect(screen.getByTestId('request-parameters')).toBeVisible();

        await user.click(screen.getByRole('tab', { name: 'Authorization' }));

        expect(screen.getByTestId('request-authorization')).toBeVisible();

        await user.click(screen.getByRole('tab', { name: 'Headers' }));

        expect(screen.getByTestId('request-headers')).toBeVisible();
    });
});
