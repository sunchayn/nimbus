import type { Meta, StoryObj } from '@storybook/vue3';
import {
    AppTable,
    AppTableBody,
    AppTableCaption,
    AppTableCell,
    AppTableFooter,
    AppTableHead,
    AppTableHeader,
    AppTableRow,
} from './index';

const meta: Meta<typeof AppTable> = {
    title: 'Base/Table',
    component: AppTable,
    tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof AppTable>;

export const Default: Story = {
    render: () => ({
        components: {
            AppTable,
            AppTableHeader,
            AppTableBody,
            AppTableHead,
            AppTableRow,
            AppTableCell,
            AppTableCaption,
            AppTableFooter,
        },
        template: `
            <AppTable>
                <AppTableCaption>A list of your recent invoices.</AppTableCaption>
                <AppTableHeader>
                    <AppTableRow>
                        <AppTableHead class="w-[100px]">Invoice</AppTableHead>
                        <AppTableHead>Status</AppTableHead>
                        <AppTableHead>Method</AppTableHead>
                        <AppTableHead class="text-right">Amount</AppTableHead>
                    </AppTableRow>
                </AppTableHeader>
                <AppTableBody>
                    <AppTableRow>
                        <AppTableCell class="font-medium">INV001</AppTableCell>
                        <AppTableCell>Paid</AppTableCell>
                        <AppTableCell>Credit Card</AppTableCell>
                        <AppTableCell class="text-right">$250.00</AppTableCell>
                    </AppTableRow>
                </AppTableBody>
                <AppTableFooter>
                    <AppTableRow>
                        <AppTableCell colspan="3">Total</AppTableCell>
                        <AppTableCell class="text-right">$250.00</AppTableCell>
                    </AppTableRow>
                </AppTableFooter>
            </AppTable>
        `,
    }),
};
