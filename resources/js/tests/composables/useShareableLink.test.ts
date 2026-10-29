import { useShareableLink } from '@/composables/request/useShareableLink';
import { createTestingPinia } from '@pinia/testing';
import { setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

/*
 * Fixtures.
 */

const toastError = vi.fn();
const encodeShareablePayload = vi.fn();
const buildShareableUrl = vi.fn();

vi.mock('@/components/base/toast', () => ({
    useAppToast: () => ({ error: toastError }),
}));

vi.mock('@/utils/shareableLinks', () => ({
    encodeShareablePayload: (...args: unknown[]) => encodeShareablePayload(...args),
    buildShareableUrl: (...args: unknown[]) => buildShareableUrl(...args),
}));

const setup = (withRequest = true) => {
    setActivePinia(
        createTestingPinia({
            createSpy: vi.fn,
            initialState: {
                tabs: withRequest
                    ? {
                          tabs: [{ id: 'tab1', request: { endpoint: 'api/users' } }],
                          activeTabId: 'tab1',
                      }
                    : { tabs: [], activeTabId: null },
            },
        }),
    );

    return useShareableLink();
};

describe('useShareableLink', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        encodeShareablePayload.mockReturnValue('encoded');
        buildShareableUrl.mockReturnValue('https://nimbus.test/share#encoded');
    });

    it('starts closed with an empty link', () => {
        // Act

        const { showShareableLinkDialog, shareableLink } = setup();

        // Assert

        expect(showShareableLinkDialog.value).toBe(false);
        expect(shareableLink.value).toBe('');
    });

    it('builds the link of the active request and opens the dialog', () => {
        // Arrange

        const { showShareableLinkDialog, shareableLink, openShareableLinkDialog } =
            setup();

        // Act

        openShareableLinkDialog();

        // Assert

        expect(encodeShareablePayload).toHaveBeenCalledOnce();
        expect(encodeShareablePayload.mock.calls[0][0]).toMatchObject({
            endpoint: 'api/users',
        });
        expect(shareableLink.value).toBe('https://nimbus.test/share#encoded');
        expect(showShareableLinkDialog.value).toBe(true);
    });

    it('does nothing without an active request', () => {
        // Arrange

        const { showShareableLinkDialog, openShareableLinkDialog } = setup(false);

        // Act

        openShareableLinkDialog();

        // Assert

        expect(encodeShareablePayload).not.toHaveBeenCalled();
        expect(showShareableLinkDialog.value).toBe(false);
    });

    it('shows an error toast and stays closed when encoding fails', () => {
        // Arrange

        encodeShareablePayload.mockImplementation(() => {
            throw new Error('boom');
        });

        const { showShareableLinkDialog, openShareableLinkDialog } = setup();

        // Act

        openShareableLinkDialog();

        // Assert

        expect(toastError).toHaveBeenCalledWith(
            'Failed to generate shareable link',
            expect.objectContaining({ description: expect.any(String) }),
        );
        expect(showShareableLinkDialog.value).toBe(false);
    });
});
