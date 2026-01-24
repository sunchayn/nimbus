import { cva, type VariantProps } from 'class-variance-authority';

export { default as AppBadge } from './AppBadge.vue';

export const badgeVariants = cva(
    'inline-flex items-center rounded-sm border border-border px-1.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus-visible:ring-1 focus-visible:ring-ring focus-visible:ring-offset-2',
    {
        variants: {
            variant: {
                default:
                    'border-transparent bg-primary text-primary-foreground shadow-sm hover:bg-primary/80',
                secondary:
                    'border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80',
                destructive:
                    'border-transparent bg-destructive text-destructive-foreground shadow-sm hover:bg-destructive/80',
                outline: 'text-foreground',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    },
);

export type BadgeVariants = VariantProps<typeof badgeVariants>;
