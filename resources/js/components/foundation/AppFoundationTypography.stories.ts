import type { Meta, StoryObj } from '@storybook/vue3';
import AppFoundationTypography from './AppFoundationTypography.vue';

const meta: Meta<typeof AppFoundationTypography> = {
    title: 'Foundation/Typography',
    component: AppFoundationTypography,
    parameters: {
        layout: 'fullscreen',
    },
};

export default meta;
type Story = StoryObj<typeof AppFoundationTypography>;

export const Scale: Story = {};
