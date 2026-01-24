import type { Meta, StoryObj } from '@storybook/vue3';
import { AppSeparator } from './index';

const meta: Meta<typeof AppSeparator> = {
    title: 'Base/Separator',
    component: AppSeparator,
    tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof AppSeparator>;

export const Horizontal: Story = {
    render: () => ({
        components: { AppSeparator },
        template: `
            <div>
                <div class="space-y-1">
                    <h4 class="text-sm font-medium leading-none">Radix UI</h4>
                    <p class="text-sm text-zinc-500">An open-source UI component library.</p>
                </div>
                <AppSeparator class="my-4" />
                <div class="flex h-5 items-center space-x-4 text-sm">
                    <div>Blog</div>
                    <AppSeparator orientation="vertical" />
                    <div>Docs</div>
                    <AppSeparator orientation="vertical" />
                    <div>Source</div>
                </div>
            </div>
        `,
    }),
};

export const WithLabel: Story = {
    args: {
        label: 'OR',
    },
};
