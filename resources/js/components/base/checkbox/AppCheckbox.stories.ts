import type { Meta, StoryObj } from '@storybook/vue3';
import { AppCheckbox } from './index';
import { AppLabel } from '../label';

const meta: Meta<typeof AppCheckbox> = {
    title: 'Base/Checkbox',
    component: AppCheckbox,
    tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof AppCheckbox>;

export const Default: Story = {
    render: () => ({
        components: { AppCheckbox, AppLabel },
        template: `
            <div class="flex items-center space-x-2">
                <AppCheckbox id="terms" />
                <AppLabel for="terms">Accept terms and conditions</AppLabel>
            </div>
        `,
    }),
};

export const Disabled: Story = {
    render: () => ({
        components: { AppCheckbox, AppLabel },
        template: `
            <div class="flex items-center space-x-2">
                <AppCheckbox id="terms2" disabled />
                <AppLabel for="terms2" class="opacity-50">Disabled checkbox</AppLabel>
            </div>
        `,
    }),
};
