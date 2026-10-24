import { type ClassValue, clsx } from 'clsx';
import { extendTailwindMerge } from 'tailwind-merge';
// The theme preset lives outside resources/js, so the alias cannot reach it.
// eslint-disable-next-line import/no-relative-parent-imports
import { twMergeConfig } from '../../../../tools/ui/theme.js';

// Knows the token utilities, so gap-base replaces gap-2 like the Nuxt UI components do.
const twMerge = extendTailwindMerge(twMergeConfig);

/**
 * Combines and merges Tailwind CSS classes intelligently.
 *
 * Uses clsx for conditional class handling and tailwind-merge to resolve
 * conflicts between Tailwind classes (e.g., 'p-2 p-4' becomes 'p-4').
 */
export function cn(...inputs: ClassValue[]): string {
    return twMerge(clsx(inputs));
}
