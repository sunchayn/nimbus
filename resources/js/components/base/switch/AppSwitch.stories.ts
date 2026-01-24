import type { Meta, StoryObj } from '@storybook/vue3';
import { AppLabel } from '../label';
import { AppSwitch } from './index';

const meta: Meta<typeof AppSwitch> = {
    title: 'Base/Switch',
    component: AppSwitch,
    tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof AppSwitch>;

export const Default: Story = {
    render: () => ({
        components: { AppSwitch, AppLabel },
        template: `
            <div class="flex items-center space-x-2">
                <AppSwitch id="airplane-mode" />
                <AppLabel for="airplane-mode">Airplane Mode</AppLabel>
            </div>
        `,
    }),
};

export const Compact: Story = {
    render: () => ({
        components: { AppSwitch, AppLabel },
        template: `
            <div class="flex items-center space-x-2">
                <AppSwitch id="notifications" :variant="{ type: 'compact' }" />
                <AppLabel for="notifications">Notifications</AppLabel>
            </div>
        `,
    }),
};
