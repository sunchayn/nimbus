export interface ShortcutDefinition {
    id: string;
    /** Keys joined with "+". "Mod" is Cmd on macOS and Ctrl elsewhere. */
    keys: string;
    description: string;
}

export const SHORTCUTS: ShortcutDefinition[] = [
    { id: 'palette', keys: 'Mod+K', description: 'Open the command palette' },
    { id: 'send', keys: 'Mod+Enter', description: 'Send the request' },
    { id: 'focus-url', keys: 'Mod+L', description: 'Focus the endpoint' },
    { id: 'toggle-explorer', keys: 'Mod+B', description: 'Show or hide the routes' },
    { id: 'switch-tab', keys: 'Alt+1..9', description: 'Switch to a tab by position' },
    { id: 'help', keys: '?', description: 'Show the shortcuts' },
];

const isMac = (): boolean => /Mac|iPhone|iPad/i.test(navigator.platform);

/**
 * Turns a shortcut definition into the keys shown on this platform.
 */
export const formatShortcut = (keys: string): string => {
    if (!isMac()) {
        return keys.replace('Mod', 'Ctrl');
    }

    return keys.replace('Mod', 'Cmd').replace('Alt', 'Opt').replaceAll('+', ' ');
};
