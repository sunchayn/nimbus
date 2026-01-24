import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

/**
 * Combines and merges Tailwind CSS classes intelligently.
 *
 * Uses clsx for conditional class handling and tailwind-merge to resolve
 * conflicts between Tailwind classes (e.g., 'p-2 p-4' becomes 'p-4').
 */
export function cn(...inputs: ClassValue[]): string {
    return twMerge(clsx(inputs));
}
