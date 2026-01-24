import type { Meta, StoryObj } from '@storybook/vue3';
import { AppBadge } from './index';

const meta: Meta<typeof AppBadge> = {
    title: 'Base/Badge',
    component: AppBadge,
    tags: ['autodocs'],
    argTypes: {
        variant: {
            control: 'select',
            options: ['default', 'secondary', 'destructive', 'outline'],
        },
    },
};

export default meta;
type Story = StoryObj<typeof AppBadge>;

export const Default: Story = {
    args: { variant: 'default' },
    render: args => ({
        components: { AppBadge },
        setup: () => ({ args }),
        template: '<AppBadge v-bind="args">Badge</AppBadge>',
    }),
};

export const AllVariants: Story = {
    render: () => ({
        components: { AppBadge },
        template: `
            <div class="flex flex-wrap gap-4">
                <AppBadge variant="default">Default</AppBadge>
                <AppBadge variant="secondary">Secondary</AppBadge>
                <AppBadge variant="destructive">Destructive</AppBadge>
                <AppBadge variant="outline">Outline</AppBadge>
            </div>
        `,
    }),
};
