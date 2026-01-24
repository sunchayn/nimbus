import type { Meta, StoryObj } from '@storybook/vue3';
import { AppButton } from './index';

const meta: Meta<typeof AppButton> = {
    title: 'Base/Button',
    component: AppButton,
    tags: ['autodocs'],
    argTypes: {
        variant: {
            control: 'select',
            options: ['default', 'destructive', 'outline', 'secondary', 'ghost', 'link'],
        },
        size: {
            control: 'select',
            options: ['default', 'xs', 'sm', 'lg', 'icon'],
        },
    },
};

export default meta;
type Story = StoryObj<typeof AppButton>;

export const Default: Story = {
    args: {
        variant: 'default',
        size: 'default',
    },
    render: (args) => ({
        components: { AppButton },
        setup() {
            return { args };
        },
        template: '<AppButton v-bind="args">Button</AppButton>',
    }),
};

export const Variants: Story = {
    render: () => ({
        components: { AppButton },
        template: `
            <div class="flex flex-wrap gap-4">
                <AppButton variant="default">Default</AppButton>
                <AppButton variant="destructive">Destructive</AppButton>
                <AppButton variant="outline">Outline</AppButton>
                <AppButton variant="secondary">Secondary</AppButton>
                <AppButton variant="ghost">Ghost</AppButton>
                <AppButton variant="link">Link</AppButton>
            </div>
        `,
    }),
};
