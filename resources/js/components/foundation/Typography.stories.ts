import type { Meta, StoryObj } from '@storybook/vue3';
import Typography from './Typography.vue';

const meta: Meta<typeof Typography> = {
    title: 'Foundation/Typography',
    component: Typography,
    parameters: {
        layout: 'fullscreen',
    },
};

export default meta;
type Story = StoryObj<typeof Typography>;

export const Scale: Story = {};
