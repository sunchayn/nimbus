import type { Meta, StoryObj } from '@storybook/vue3';
import {
    AppCard,
    AppCardContent,
    AppCardDescription,
    AppCardFooter,
    AppCardHeader,
    AppCardTitle,
} from './index';

const meta: Meta<typeof AppCard> = {
    title: 'Base/Card',
    component: AppCard,
    tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof AppCard>;

export const Default: Story = {
    render: () => ({
        components: {
            AppCard,
            AppCardContent,
            AppCardDescription,
            AppCardFooter,
            AppCardHeader,
            AppCardTitle,
        },
        template: `
            <AppCard class="w-[350px]">
                <AppCardHeader>
                    <AppCardTitle>Card Title</AppCardTitle>
                    <AppCardDescription>Card Description providing more context.</AppCardDescription>
                </AppCardHeader>
                <AppCardContent>
                    <p>This is the main content of the card. It can contain anything.</p>
                </AppCardContent>
                <AppCardFooter class="flex justify-between">
                    <button class="px-4 py-2 text-sm font-medium text-zinc-900 bg-zinc-100 rounded-md">Cancel</button>
                    <button class="px-4 py-2 text-sm font-medium text-zinc-50 bg-zinc-900 rounded-md">Deploy</button>
                </AppCardFooter>
            </AppCard>
        `,
    }),
};
