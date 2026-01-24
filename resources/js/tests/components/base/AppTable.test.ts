import type { VueWrapper } from '@vue/test-utils';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { AppTable, AppTableBody, AppTableCell, AppTableHead, AppTableHeader, AppTableRow } from '@/components/base/table';

/*
 * Fixtures.
 */

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */
const createWrapper = (options= {}): VueWrapper => {
    return mount({
        components: { AppTable, AppTableHeader, AppTableBody, AppTableHead, AppTableRow, AppTableCell },
        template: `
            <AppTable>
                <AppTableHeader>
                    <AppTableRow>
                        <AppTableHead>Header</AppTableHead>
                    </AppTableRow>
                </AppTableHeader>
                <AppTableBody>
                    <AppTableRow>
                        <AppTableCell>Cell</AppTableCell>
                    </AppTableRow>
                </AppTableBody>
            </AppTable>
        `,
        ...options,
    }, {
        global: {
            plugins: [createPinia()],
        },
    });
};

describe('AppTable', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    /*
     * Rendering tests.
     */

    describe('Rendering', () => {
        it('renders table structure correctly', () => {
            // Arrange

            const wrapper = createWrapper();

            // Assert

            expect(wrapper.find('table').exists()).toBe(true);
            expect(wrapper.find('thead').exists()).toBe(true);
            expect(wrapper.find('tbody').exists()).toBe(true);
            expect(wrapper.find('th').text()).toBe('Header');
            expect(wrapper.find('td').text()).toBe('Cell');
        });
    });
});
