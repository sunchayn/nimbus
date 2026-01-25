import { cva, type VariantProps } from 'class-variance-authority';

export { default as AppSelect } from './AppSelect.vue';
export { default as AppSelectContent } from './AppSelectContent.vue';
export { default as AppSelectGroup } from './AppSelectGroup.vue';
export { default as AppSelectItem } from './AppSelectItem.vue';
export { default as AppSelectItemText } from './AppSelectItemText.vue';
export { default as AppSelectLabel } from './AppSelectLabel.vue';
export { default as AppSelectScrollDownButton } from './AppSelectScrollDownButton.vue';
export { default as AppSelectScrollUpButton } from './AppSelectScrollUpButton.vue';
export { default as AppSelectSeparator } from './AppSelectSeparator.vue';
export { default as AppSelectTrigger } from './AppSelectTrigger.vue';
export { default as AppSelectValue } from './AppSelectValue.vue';

export const selectTriggerVariants = cva(
    'flex h-9 items-center justify-between px-3 py-2 text-start text-sm whitespace-nowrap focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 data-[placeholder]:text-zinc-500 dark:data-[placeholder]:text-zinc-400 [&>span]:truncate',
    {
        variants: {
            variant: {
                default:
                    'rounded-md border border-zinc-200 bg-transparent shadow-sm ring-offset-white focus:ring-2 focus:ring-zinc-950 dark:border-zinc-800 dark:ring-offset-zinc-950 dark:focus:ring-zinc-300',
                toolbar: 'rounded-none border-0 bg-transparent shadow-none focus:ring-0',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    },
);

export type SelectTriggerVariants = VariantProps<typeof selectTriggerVariants>;
