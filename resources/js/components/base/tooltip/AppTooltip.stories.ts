import type { Meta, StoryObj } from '@storybook/vue3';
import { AppTooltip, AppTooltipContent, AppTooltipProvider, AppTooltipTrigger } from './index';
import { AppButton } from '../button';

const meta: Meta<typeof AppTooltip> = {
    title: 'Base/Tooltip',
    component: AppTooltip,
    tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof AppTooltip>;

export const Default: Story = {
    render: () => ({
        components: { AppTooltip, AppTooltipTrigger, AppTooltipContent, AppTooltipProvider, AppButton },
        template: `
            <AppTooltipProvider>
                <AppTooltip>
                    <AppTooltipTrigger as-child>
                        <AppButton variant="outline">Hover me</AppButton>
                    </AppTooltipTrigger>
                    <AppTooltipContent>
                        <p>Add to library</p>
                    </AppTooltipContent>
                </AppTooltip>
            </AppTooltipProvider>
        `,
    }),
};
