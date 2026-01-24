import type { Meta, StoryObj } from '@storybook/vue3';
import { AppLabel } from './index';

const meta: Meta<typeof AppLabel> = {
    title: 'Base/Label',
    component: AppLabel,
    tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof AppLabel>;

export const Default: Story = {
    render: () => ({
        components: { AppLabel },
        template: '<AppLabel>Email Address</AppLabel>',
    }),
};
