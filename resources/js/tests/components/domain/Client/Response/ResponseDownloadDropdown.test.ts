import useResponseDownload from '@/composables/response/useResponseDownload';
import type { RequestLog } from '@/interfaces/history/logs';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { ComputedRef } from 'vue';
import { ref } from 'vue';
import ResponseDownloadDropdown from '../../../../../components/domain/Client/Response/ResponseBody/ResponseDownloadDropdown.vue';

vi.mock('@/composables/response/useResponseDownload');

describe('ResponseDownloadDropdown', () => {
    const mockDownloadRawResponse = vi.fn();
    const mockDownloadResponseShape = vi.fn();
    const mockIsResolvingShape = ref(false);
    const mockResolvedShape = ref(null);
    const mockIsJsonResponse = ref(true);

    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();

        vi.mocked(useResponseDownload).mockReturnValue({
            isResolvingShape: mockIsResolvingShape,
            resolvedShape: mockResolvedShape,
            isJsonResponse: mockIsJsonResponse as unknown as ComputedRef<boolean>,
            downloadRawResponse: mockDownloadRawResponse,
            downloadResponseShape: mockDownloadResponseShape,
        });

        mockIsResolvingShape.value = false;
        mockResolvedShape.value = null;
        mockIsJsonResponse.value = true;
    });

    const createWrapper = (props = {}) => {
        return mount(ResponseDownloadDropdown, {
            attachTo: document.body,
            props: {
                response: {} as RequestLog,
                ...props,
            },
        });
    };

    it('renders the dropdown trigger button', () => {
        const wrapper = createWrapper();
        const trigger = wrapper.find('[data-testid="download-dropdown-trigger"]');
        expect(trigger.exists()).toBe(true);
        expect(trigger.text()).toContain('Download');
    });

    it('displays loading spinner and disables option when prefetching shape', async () => {
        mockIsResolvingShape.value = true;
        const wrapper = createWrapper();

        // Open the dropdown menu to render options in tests
        const trigger = wrapper.find('[data-testid="download-dropdown-trigger"]');
        await trigger.trigger('click');

        const shapeOption = document.querySelector(
            '[data-testid="download-shape-option"]',
        );
        expect(shapeOption).not.toBeNull();
        expect(shapeOption?.getAttribute('disabled')).toBeDefined();

        const spinner = shapeOption?.querySelector('.animate-spin');
        expect(spinner).not.toBeNull();
    });

    it('disables the Response Shape option if the response is not json', async () => {
        mockIsJsonResponse.value = false;
        const wrapper = createWrapper();

        const trigger = wrapper.find('[data-testid="download-dropdown-trigger"]');
        await trigger.trigger('click');

        const shapeOption = document.querySelector(
            '[data-testid="download-shape-option"]',
        );
        expect(shapeOption).not.toBeNull();
        expect(shapeOption?.getAttribute('disabled')).toBeDefined();
    });

    it('triggers correct actions on option clicks', async () => {
        const wrapper = createWrapper();

        const trigger = wrapper.find('[data-testid="download-dropdown-trigger"]');
        await trigger.trigger('click');

        const rawOption = document.querySelector('[data-testid="download-raw-option"]');
        (rawOption as HTMLElement)?.click();
        expect(mockDownloadRawResponse).toHaveBeenCalledTimes(1);

        const shapeOption = document.querySelector(
            '[data-testid="download-shape-option"]',
        );
        (shapeOption as HTMLElement)?.click();
        expect(mockDownloadResponseShape).toHaveBeenCalledTimes(1);
    });
});
