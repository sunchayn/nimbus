import { formatShortcut } from '@/config/shortcuts';
import { afterEach, describe, expect, it, vi } from 'vitest';

describe('formatShortcut', () => {
    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('uses Ctrl on other platforms', () => {
        // Arrange

        vi.spyOn(navigator, 'platform', 'get').mockReturnValue('Win32');

        // Act & Assert

        expect(formatShortcut('Mod+Enter')).toBe('Ctrl+Enter');
    });

    it('uses the command key on macOS', () => {
        // Arrange

        vi.spyOn(navigator, 'platform', 'get').mockReturnValue('MacIntel');

        // Act & Assert

        expect(formatShortcut('Mod+K')).toBe('Cmd K');
    });

    it('uses the option key on macOS', () => {
        // Arrange

        vi.spyOn(navigator, 'platform', 'get').mockReturnValue('MacIntel');

        // Act & Assert

        expect(formatShortcut('Alt+1..9')).toBe('Opt 1..9');
    });

    it('keeps Alt on other platforms', () => {
        // Arrange

        vi.spyOn(navigator, 'platform', 'get').mockReturnValue('Win32');

        // Act & Assert

        expect(formatShortcut('Alt+1..9')).toBe('Alt+1..9');
    });
});
