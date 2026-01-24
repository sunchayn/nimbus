import type { Meta, StoryObj } from '@storybook/vue3';
import { AppButton } from '../button';
import { AppPopover, AppPopoverContent, AppPopoverTrigger } from './index';

const meta: Meta<typeof AppPopover> = {
    title: 'Base/Popover',
    component: AppPopover,
    tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof AppPopover>;

export const Default: Story = {
    render: () => ({
        components: { AppPopover, AppPopoverTrigger, AppPopoverContent, AppButton },
        template: `
            <AppPopover>
                <AppPopoverTrigger asChild>
                    <AppButton variant="outline">Open Popover</AppButton>
                </AppPopoverTrigger>
                <AppPopoverContent class="w-80">
                    <div class="grid gap-4">
                        <div class="space-y-2">
                            <h4 class="font-medium leading-none">Dimensions</h4>
                            <p class="text-sm text-zinc-500">Set the dimensions for the layer.</p>
                        </div>
                    </div>
                </AppPopoverContent>
            </AppPopover>
        `,
    }),
};
