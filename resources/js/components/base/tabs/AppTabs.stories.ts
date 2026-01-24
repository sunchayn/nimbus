import type { Meta, StoryObj } from '@storybook/vue3';
import { AppTabs, AppTabsContent, AppTabsList, AppTabsTrigger } from './index';

const meta: Meta<typeof AppTabs> = {
    title: 'Base/Tabs',
    component: AppTabs,
    tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof AppTabs>;

export const Default: Story = {
    render: () => ({
        components: { AppTabs, AppTabsList, AppTabsTrigger, AppTabsContent },
        template: `
            <AppTabs default-value="account" class="w-[400px]">
                <AppTabsList>
                    <AppTabsTrigger value="account">Account</AppTabsTrigger>
                    <AppTabsTrigger value="password">Password</AppTabsTrigger>
                </AppTabsList>
                <AppTabsContent value="account">
                    <div class="p-4 border border-t-0 rounded-b-md">
                        Make changes to your account here.
                    </div>
                </AppTabsContent>
                <AppTabsContent value="password">
                    <div class="p-4 border border-t-0 rounded-b-md">
                        Change your password here.
                    </div>
                </AppTabsContent>
            </AppTabs>
        `,
    }),
};
