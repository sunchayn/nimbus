import type { Meta, StoryObj } from '@storybook/vue3';
import ColorPalette from './ColorPalette.vue';

const meta: Meta<typeof ColorPalette> = {
    title: 'Foundation/Colors',
    component: ColorPalette,
    parameters: {
        layout: 'fullscreen',
    },
};

export default meta;
type Story = StoryObj<typeof ColorPalette>;

export const Palette: Story = {};
