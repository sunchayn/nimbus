import type { Meta, StoryObj } from '@storybook/vue3';
import AppInput from './AppInput.vue';

const meta: Meta<typeof AppInput> = {
    title: 'Base/Input',
    component: AppInput,
    tags: ['autodocs'],
    argTypes: {
        type: {
            control: 'select',
            options: ['text', 'password', 'email', 'number', 'tel', 'url'],
        },
    },
};

export default meta;
type Story = StoryObj<typeof AppInput>;

export const Default: Story = {
    args: {
        modelValue: '',
        placeholder: 'Type something...',
    },
    render: args => ({
        components: { AppInput },
        setup: () => ({ args }),
        template: '<AppInput v-bind="args" />',
    }),
};

export const Password: Story = {
    args: {
        type: 'password',
        modelValue: 'secret',
    },
};

export const Disabled: Story = {
    args: {
        disabled: true,
        modelValue: 'Cannot edit this',
    },
};
