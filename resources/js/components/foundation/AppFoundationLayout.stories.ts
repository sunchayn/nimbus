import type { Meta, StoryObj } from '@storybook/vue3';
import AppFoundationLayout from './AppFoundationLayout.vue';

const meta: Meta<typeof AppFoundationLayout> = {
    title: 'Foundation/Layout',
    component: AppFoundationLayout,
    parameters: {
        layout: 'fullscreen',
    },
};

export default meta;
type Story = StoryObj<typeof AppFoundationLayout>;

export const SpacingAndRadii: Story = {};
