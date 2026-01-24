import type { Meta, StoryObj } from '@storybook/vue3';
import { AppDropdownMenu, AppDropdownMenuContent, AppDropdownMenuItem, AppDropdownMenuLabel, AppDropdownMenuSeparator, AppDropdownMenuTrigger } from './index';
import { AppButton } from '../button';

const meta: Meta<typeof AppDropdownMenu> = {
    title: 'Base/DropdownMenu',
    component: AppDropdownMenu,
    tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof AppDropdownMenu>;

export const Default: Story = {
    render: () => ({
        components: { AppDropdownMenu, AppDropdownMenuTrigger, AppDropdownMenuContent, AppDropdownMenuItem, AppDropdownMenuLabel, AppDropdownMenuSeparator, AppButton },
        template: `
            <AppDropdownMenu>
                <AppDropdownMenuTrigger asChild>
                    <AppButton variant="outline">Open Menu</AppButton>
                </AppDropdownMenuTrigger>
                <AppDropdownMenuContent class="w-56">
                    <AppDropdownMenuLabel>My Account</AppDropdownMenuLabel>
                    <AppDropdownMenuSeparator />
                    <AppDropdownMenuItem>Profile</AppDropdownMenuItem>
                    <AppDropdownMenuItem>Billing</AppDropdownMenuItem>
                    <AppDropdownMenuItem>Team</AppDropdownMenuItem>
                    <AppDropdownMenuItem>Subscription</AppDropdownMenuItem>
                </AppDropdownMenuContent>
            </AppDropdownMenu>
        `,
    }),
};
