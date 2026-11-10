import DiagnosticsDialog from '@/components/domain/Settings/DiagnosticsDialog.vue';
import { flushPromises, mount } from '@vue/test-utils';
import axios from 'axios';
import { createPinia, setActivePinia } from 'pinia';
import type { Mocked } from 'vitest';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

/*
 * Fixtures.
 */

vi.mock('axios');
const mockedAxios = axios as Mocked<typeof axios>;

const mountDialog = () => mount(DiagnosticsDialog, { attachTo: document.body });

describe('DiagnosticsDialog', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    afterEach(() => {
        document.body.innerHTML = '';
        vi.resetAllMocks();
    });

    it('lists the checks returned by the server', async () => {
        // Arrange

        mockedAxios.get.mockResolvedValue({
            data: [
                { name: 'Config', status: 'ok', message: 'Valid.' },
                { name: 'Guard', status: 'failed', message: 'Missing guard.' },
            ],
        });

        const wrapper = mountDialog();

        // Act

        await wrapper.get('[data-testid="diagnostics-button"]').trigger('click');
        await flushPromises();

        // Assert

        expect(mockedAxios.get).toHaveBeenCalledWith('/api/doctor');
        expect(document.querySelector('[data-testid="diagnostic-ok"]')).not.toBeNull();
        expect(document.body.textContent).toContain('Missing guard.');
    });

    it('shows an error when the checks cannot run', async () => {
        // Arrange

        mockedAxios.get.mockRejectedValue(new Error('Network Error'));

        const wrapper = mountDialog();

        // Act

        await wrapper.get('[data-testid="diagnostics-button"]').trigger('click');
        await flushPromises();

        // Assert

        expect(
            document.querySelector('[data-testid="diagnostics-error"]')?.textContent,
        ).toContain('Network Error');
    });
});
