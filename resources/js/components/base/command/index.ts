import type {
    CommandPaletteGroup,
    CommandPaletteItem,
} from '@nuxt/ui/components/CommandPalette.vue';

export { default as AppCommand } from './AppCommand.vue';

export type AppCommandItem = CommandPaletteItem;
export type AppCommandGroup<T extends CommandPaletteItem = CommandPaletteItem> =
    CommandPaletteGroup<T>;
