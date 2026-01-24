import type { Meta, StoryObj } from '@storybook/vue3';
import Layout from './Layout.vue';

const meta: Meta<typeof Layout> = {
    title: 'Foundation/Layout',
    component: Layout,
    parameters: {
        layout: 'fullscreen',
    },
};

export default meta;
type Story = StoryObj<typeof Layout>;

export const SpacingAndRadii: Story = {};
