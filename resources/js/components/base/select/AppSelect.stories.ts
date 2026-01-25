import type { Meta, StoryObj } from '@storybook/vue3';
import {
    AppSelect,
    AppSelectContent,
    AppSelectGroup,
    AppSelectItem,
    AppSelectLabel,
    AppSelectSeparator,
    AppSelectTrigger,
    AppSelectValue,
} from './index';

const meta: Meta<typeof AppSelect> = {
    title: 'Base/Select',
    component: AppSelect,
    tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof AppSelect>;

export const Default: Story = {
    render: () => ({
        components: {
            AppSelect,
            AppSelectContent,
            AppSelectGroup,
            AppSelectItem,
            AppSelectLabel,
            AppSelectSeparator,
            AppSelectTrigger,
            AppSelectValue,
        },
        template: `
            <AppSelect>
                <AppSelectTrigger class="w-[180px]">
                    <AppSelectValue placeholder="Select a fruit" />
                </AppSelectTrigger>
                <AppSelectContent>
                    <AppSelectGroup>
                        <AppSelectLabel>Fruits</AppSelectLabel>
                        <AppSelectItem value="apple">Apple</AppSelectItem>
                        <AppSelectItem value="banana">Banana</AppSelectItem>
                        <AppSelectItem value="blueberry">Blueberry</AppSelectItem>
                        <AppSelectItem value="grapes">Grapes</AppSelectItem>
                        <AppSelectItem value="pineapple">Pineapple</AppSelectItem>
                    </AppSelectGroup>
                </AppSelectContent>
            </AppSelect>
        `,
    }),
};
export const Toolbar: Story = {
    render: () => ({
        components: {
            AppSelect,
            AppSelectContent,
            AppSelectGroup,
            AppSelectItem,
            AppSelectLabel,
            AppSelectTrigger,
            AppSelectValue,
        },
        template: `
            <AppSelect>
                <AppSelectTrigger variant="toolbar" class="w-[180px]">
                    <AppSelectValue placeholder="Select a Method" />
                </AppSelectTrigger>
                <AppSelectContent>
                    <AppSelectGroup>
                        <AppSelectLabel>Methods</AppSelectLabel>
                        <AppSelectItem value="GET">GET</AppSelectItem>
                        <AppSelectItem value="POST">POST</AppSelectItem>
                        <AppSelectItem value="PUT">PUT</AppSelectItem>
                        <AppSelectItem value="DELETE">DELETE</AppSelectItem>
                    </AppSelectGroup>
                </AppSelectContent>
            </AppSelect>
        `,
    }),
};
