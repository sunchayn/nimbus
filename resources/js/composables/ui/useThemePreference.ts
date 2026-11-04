import { usePreferredDark } from '@vueuse/core';
import { computed, type ComputedRef, type MaybeRefOrGetter, toValue, watch } from 'vue';

export type ThemePreference =
    | 'light'
    | 'dark'
    | 'system'
    | 'solarized-light'
    | 'solarized-dark'
    | 'sakura';

/*
 * Named themes override tokens under their own data-theme selector.
 * The value tells whether the theme is a dark one, so the `dark` class still matches.
 */
export const NAMED_THEMES: Record<string, { dark: boolean }> = {
    'solarized-light': { dark: false },
    'solarized-dark': { dark: true },
    sakura: { dark: false },
};

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
 * Toggles the `dark` class and the `data-theme` attribute on the root element from the user preference.
 * The `system` preference follows the OS and stops listening when the scope is disposed.
 */
export const useThemePreference = (
    preference: MaybeRefOrGetter<ThemePreference>,
): { isDark: ComputedRef<boolean> } => {
    const prefersDark = usePreferredDark();

    const isDark = computed(() => {
        const value = toValue(preference);

        if (value in NAMED_THEMES) {
            return NAMED_THEMES[value].dark;
        }

        return value === 'system' ? prefersDark.value : value === 'dark';
    });

    watch(
        [isDark, () => toValue(preference)],
        ([dark, value]) =>
            withoutTransitions(() => {
                const root = document.documentElement;

                root.classList.toggle('dark', dark);

                if (value in NAMED_THEMES) {
                    root.dataset.theme = value;
                } else {
                    delete root.dataset.theme;
                }
            }),
        { immediate: true },
    );

    return { isDark };
};
