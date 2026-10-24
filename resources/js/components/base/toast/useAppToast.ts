import { useToast } from '@nuxt/ui/composables/useToast';

export interface AppToastOptions {
    description?: string;
    duration?: number;
}

export interface UseAppToastResult {
    success: (title: string, options?: AppToastOptions) => void;
    warning: (title: string, options?: AppToastOptions) => void;
    error: (title: string, options?: AppToastOptions) => void;
}

/**
 * Shows toast notifications through the toaster configured on the root UApp.
 */
export function useAppToast(): UseAppToastResult {
    const toast = useToast();

    const notify =
        (color: 'success' | 'warning' | 'error') =>
        (title: string, options: AppToastOptions = {}): void => {
            toast.add({ title, color, ...options });
        };

    return {
        success: notify('success'),
        warning: notify('warning'),
        error: notify('error'),
    };
}
