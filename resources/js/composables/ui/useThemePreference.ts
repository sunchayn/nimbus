import { usePreferredDark } from '@vueuse/core';
import { computed, type ComputedRef, type MaybeRefOrGetter, toValue, watch } from 'vue';

export type ThemePreference = 'light' | 'dark' | 'system';

/*
 * Every color changes at once on a theme switch, so transitions are paused for that frame.
 * Reading a layout property forces the browser to apply the change before they return.
 */
const withoutTransitions = (change: () => void) => {
    const style = document.createElement('style');
    style.textContent = '*,*::before,*::after{transition:none !important}';
    document.head.appendChild(style);

    change();
    void document.documentElement.offsetHeight;

    requestAnimationFrame(() => style.remove());
};

/**
 * Toggles the `dark` class on the root element from the user preference.
 * The `system` preference follows the OS and stops listening when the scope is disposed.
 */
export const useThemePreference = (
    preference: MaybeRefOrGetter<ThemePreference>,
): { isDark: ComputedRef<boolean> } => {
    const prefersDark = usePreferredDark();

    const isDark = computed(() => {
        const value = toValue(preference);

        return value === 'system' ? prefersDark.value : value === 'dark';
    });

    watch(
        isDark,
        dark =>
            withoutTransitions(() =>
                document.documentElement.classList.toggle('dark', dark),
            ),
        { immediate: true },
    );

    return { isDark };
};
