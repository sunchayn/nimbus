import {
    type ThemePreference,
    useThemePreference,
} from '@/composables/ui/useThemePreference';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { effectScope, nextTick, ref } from 'vue';

type ChangeListener = (event: { matches: boolean }) => void;

describe('useThemePreference', () => {
    let systemIsDark: boolean;
    let listeners: Set<ChangeListener>;

    const setSystemDark = (matches: boolean) => {
        systemIsDark = matches;
        listeners.forEach(listener => listener({ matches }));
    };

    beforeEach(() => {
        systemIsDark = false;
        listeners = new Set();

        vi.stubGlobal(
            'matchMedia',
            vi.fn(() => ({
                get matches() {
                    return systemIsDark;
                },
                addEventListener: (_: string, listener: ChangeListener) =>
                    listeners.add(listener),
                removeEventListener: (_: string, listener: ChangeListener) =>
                    listeners.delete(listener),
            })),
        );
        vi.stubGlobal('requestAnimationFrame', (callback: () => void) => callback());
        document.documentElement.classList.remove('dark');
        delete document.documentElement.dataset.theme;
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('applies the dark class for the dark preference', () => {
        const scope = effectScope();
        scope.run(() => useThemePreference('dark'));

        expect(document.documentElement.classList.contains('dark')).toBe(true);
        scope.stop();
    });

    it('follows the system when the preference is system', async () => {
        const scope = effectScope();
        scope.run(() => useThemePreference('system'));

        expect(document.documentElement.classList.contains('dark')).toBe(false);

        setSystemDark(true);
        await nextTick();

        expect(document.documentElement.classList.contains('dark')).toBe(true);
        scope.stop();
    });

    it('ignores system changes when the preference is explicit', async () => {
        const scope = effectScope();
        scope.run(() => useThemePreference('light'));

        setSystemDark(true);
        await nextTick();

        expect(document.documentElement.classList.contains('dark')).toBe(false);
        scope.stop();
    });

    it('reacts to preference changes', async () => {
        const preference = ref<'light' | 'dark' | 'system'>('light');
        const scope = effectScope();
        scope.run(() => useThemePreference(preference));

        preference.value = 'dark';
        await nextTick();

        expect(document.documentElement.classList.contains('dark')).toBe(true);
        scope.stop();
    });

    it('sets data-theme and the dark class for a dark named theme', () => {
        const scope = effectScope();
        scope.run(() => useThemePreference('solarized-dark'));

        expect(document.documentElement.dataset.theme).toBe('solarized-dark');
        expect(document.documentElement.classList.contains('dark')).toBe(true);
        scope.stop();
    });

    it('keeps the dark class off for a light named theme', () => {
        const scope = effectScope();
        scope.run(() => useThemePreference('sakura'));

        expect(document.documentElement.dataset.theme).toBe('sakura');
        expect(document.documentElement.classList.contains('dark')).toBe(false);
        scope.stop();
    });

    it('removes data-theme when switching back to a base theme', async () => {
        const preference = ref<ThemePreference>('sakura');
        const scope = effectScope();
        scope.run(() => useThemePreference(preference));

        preference.value = 'light';
        await nextTick();

        expect(document.documentElement.dataset.theme).toBeUndefined();
        scope.stop();
    });

    it('removes the media listener when the scope stops', () => {
        const scope = effectScope();
        scope.run(() => useThemePreference('system'));

        expect(listeners.size).toBe(1);

        scope.stop();

        expect(listeners.size).toBe(0);
    });

    it('removes the temporary transition style after the change', () => {
        const scope = effectScope();
        scope.run(() => useThemePreference('dark'));

        const pausedStyles = [...document.head.querySelectorAll('style')].filter(style =>
            style.textContent?.includes('transition:none'),
        );

        expect(pausedStyles).toHaveLength(0);
        scope.stop();
    });
});
