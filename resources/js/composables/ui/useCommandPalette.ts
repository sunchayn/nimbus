import { shallowRef, type ShallowRef } from 'vue';

export type CommandPaletteMode = 'all' | 'routes';

// The state is shared, so the shortcuts and the palette component stay in sync.
const isPaletteOpen = shallowRef(false);
const paletteMode = shallowRef<CommandPaletteMode>('all');
const isHelpOpen = shallowRef(false);
const isToolboxOpen = shallowRef(false);

export const useCommandPalette = (): {
    isPaletteOpen: ShallowRef<boolean>;
    paletteMode: ShallowRef<CommandPaletteMode>;
    isHelpOpen: ShallowRef<boolean>;
    isToolboxOpen: ShallowRef<boolean>;
    openPalette: (mode?: CommandPaletteMode) => void;
    closePalette: () => void;
    togglePalette: () => void;
} => {
    const openPalette = (mode: CommandPaletteMode = 'all') => {
        paletteMode.value = mode;
        isHelpOpen.value = false;
        isPaletteOpen.value = true;
    };

    const closePalette = () => {
        isPaletteOpen.value = false;
    };

    const togglePalette = () =>
        isPaletteOpen.value ? closePalette() : openPalette('all');

    return {
        isPaletteOpen,
        paletteMode,
        isHelpOpen,
        isToolboxOpen,
        openPalette,
        closePalette,
        togglePalette,
    };
};
