import { AppTable } from '@/components/base/table';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

/*
 * Fixtures.
 */

const columns = [
    { accessorKey: 'name', header: 'Name' },
    { accessorKey: 'role', header: 'Role' },
];

const data = [
    { name: 'Ada', role: 'Admin' },
    { name: 'Bob', role: 'Guest' },
];

const createWrapper = (props = {}, slots = {}) =>
    mount(AppTable, { props: { data, columns, ...props }, slots });

describe('AppTable', () => {
    describe('Rendering', () => {
        it('renders the column headers', () => {
            // Arrange & Act

            const wrapper = createWrapper();

            // Assert

            expect(wrapper.findAll('th').map(th => th.text())).toEqual(['Name', 'Role']);
        });

        it('renders one row per data item', () => {
            // Arrange & Act

            const wrapper = createWrapper();

            // Assert

            expect(wrapper.findAll('tbody tr')).toHaveLength(2);
            expect(wrapper.text()).toContain('Ada');
            expect(wrapper.text()).toContain('Guest');
        });

        it('renders custom cell content through a cell slot', () => {
            // Arrange & Act

            const wrapper = createWrapper(
                {},
                {
                    'name-cell':
                        '<template #name-cell="{ row }">[{{ row.original.name }}]</template>',
                },
            );

            // Assert

            expect(wrapper.text()).toContain('[Ada]');
        });

        it('shows the empty text when there is no data', () => {
            // Arrange & Act

            const wrapper = createWrapper({ data: [], empty: 'Nothing here' });

            // Assert

            expect(wrapper.text()).toContain('Nothing here');
        });
    });
});
