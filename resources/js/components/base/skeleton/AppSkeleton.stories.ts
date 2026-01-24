import type { Meta, StoryObj } from '@storybook/vue3';
import { AppSkeleton } from './index';

const meta: Meta<typeof AppSkeleton> = {
    title: 'Base/Skeleton',
    component: AppSkeleton,
    tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof AppSkeleton>;

export const Default: Story = {
    render: () => ({
        components: { AppSkeleton },
        template: `
            <div class="flex items-center space-x-4">
                <AppSkeleton class="h-12 w-12 rounded-full" />
                <div class="space-y-2">
                    <AppSkeleton class="h-4 w-[250px]" />
                    <AppSkeleton class="h-4 w-[200px]" />
                </div>
            </div>
        `,
    }),
};
