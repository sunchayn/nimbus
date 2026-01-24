import type { Meta, StoryObj } from '@storybook/vue3';
import { AppButton } from '../button';
import {
    AppDialog,
    AppDialogContent,
    AppDialogDescription,
    AppDialogFooter,
    AppDialogHeader,
    AppDialogTitle,
    AppDialogTrigger,
} from './index';

const meta: Meta<typeof AppDialog> = {
    title: 'Base/Dialog',
    component: AppDialog,
    tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof AppDialog>;

export const Default: Story = {
    render: () => ({
        components: {
            AppDialog,
            AppDialogTrigger,
            AppDialogContent,
            AppDialogHeader,
            AppDialogTitle,
            AppDialogDescription,
            AppDialogFooter,
            AppButton,
        },
        template: `
            <AppDialog>
                <AppDialogTrigger asChild>
                    <AppButton variant="outline">Open Dialog</AppButton>
                </AppDialogTrigger>
                <AppDialogContent class="sm:max-w-[425px]">
                    <AppDialogHeader>
                        <AppDialogTitle>Edit profile</AppDialogTitle>
                        <AppDialogDescription>
                            Make changes to your profile here. Click save when you're done.
                        </AppDialogDescription>
                    </AppDialogHeader>
                    <div class="grid gap-4 py-4">
                        <div class="grid grid-cols-4 items-center gap-4">
                            <label class="text-right text-sm">Name</label>
                            <input class="col-span-3 h-9 px-3 border rounded-md" value="Pedro Duarte" />
                        </div>
                    </div>
                    <AppDialogFooter>
                        <AppButton type="submit">Save changes</AppButton>
                    </AppDialogFooter>
                </AppDialogContent>
            </AppDialog>
        `,
    }),
};
