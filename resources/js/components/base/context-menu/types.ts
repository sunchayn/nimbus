import type { ContextMenuItem } from '@nuxt/ui/components/ContextMenu.vue';

export type AppContextMenuItem = ContextMenuItem & {
    /** Runs once the menu is closed, after `onSelect`. */
    afterClose?: () => void;
};
