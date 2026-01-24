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
