import ResponseDownload from '@/components/domain/Client/Response/ResponseBody/ResponseDownload.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

/*
 * Fixtures.
 */

const download = {
    filename: 'report.pdf',
    mimeType: 'application/pdf',
    sizeInBytes: 2048,
};

describe('ResponseDownload', () => {
    it('renders the forwarded download metadata', () => {
        const wrapper = mount(ResponseDownload, { props: { download } });

        expect(wrapper.text()).toContain('Download forwarded');
        expect(wrapper.get('[data-testid="download-filename"]').text()).toBe(
            'report.pdf',
        );
        expect(wrapper.find('[data-testid="download-path"]').exists()).toBe(false);
        expect(wrapper.get('[data-testid="download-mime-type"]').text()).toBe(
            'application/pdf',
        );
        expect(wrapper.get('[data-testid="download-size"]').text()).toBe('2.05kB');
    });
});
