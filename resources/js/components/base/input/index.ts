import { cva, type VariantProps } from 'class-variance-authority';

export { default as AppInput } from './AppInput.vue';

export const inputVariants = cva(
    'flex h-9 w-full bg-transparent px-3 py-1 text-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-zinc-500 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 dark:placeholder:text-zinc-400',
    {
        variants: {
            variant: {
                default:
                    'rounded-md border border-zinc-200 shadow-sm focus-visible:ring-1 focus-visible:ring-zinc-950 dark:border-zinc-800 dark:focus-visible:ring-zinc-300',
                toolbar: 'rounded-none border-0 shadow-none focus-visible:ring-0',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    },
);

export type InputVariants = VariantProps<typeof inputVariants>;
