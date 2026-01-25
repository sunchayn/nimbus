import type { Meta, StoryObj } from '@storybook/vue3';
import {
    AppPanel,
    AppPanelContent,
    AppPanelDescription,
    AppPanelHeader,
    AppPanelTitle,
} from './index';

const meta: Meta<typeof AppPanel> = {
    title: 'Base/Panel',
    component: AppPanel,
    tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof AppPanel>;

export const Default: Story = {
    render: () => ({
        components: {
            AppPanel,
            AppPanelContent,
            AppPanelDescription,
            AppPanelHeader,
            AppPanelTitle,
        },
        template: `
            <AppPanel class="w-[400px] border">
                <AppPanelHeader>
                    <div class="flex flex-col">
                        <AppPanelTitle>Panel Title</AppPanelTitle>
                        <AppPanelDescription>Dense panel description for context.</AppPanelDescription>
                    </div>
                </AppPanelHeader>
                <AppPanelContent class="border-t">
                    <p class="text-xs">This is the main content of the panel. It uses px-panel for padding and is denser than a card.</p>
                </AppPanelContent>
                <AppPanelContent class="border-t">
                    <p class="text-xs">Multiple content sections can be used with borders.</p>
                </AppPanelContent>
            </AppPanel>
        `,
    }),
};
