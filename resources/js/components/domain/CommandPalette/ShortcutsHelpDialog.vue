<script setup lang="ts">
/**
 * @component ShortcutsHelpDialog
 * @description An overlay that lists every keyboard shortcut. It opens on the question mark key.
 */
import { AppDialog } from '@/components/base/dialog';
import { useCommandPalette } from '@/composables/ui/useCommandPalette';
import { formatShortcut, SHORTCUTS } from '@/config/shortcuts';

const { isHelpOpen } = useCommandPalette();
</script>

<template>
    <AppDialog v-model:open="isHelpOpen" title="Keyboard shortcuts">
        <template #body>
            <ul class="gap-base flex flex-col" data-testid="shortcuts-list">
                <li
                    v-for="shortcut in SHORTCUTS"
                    :key="shortcut.id"
                    class="gap-base flex items-center justify-between text-sm"
                >
                    <span>{{ shortcut.description }}</span>
                    <kbd
                        class="bg-muted border-default px-base rounded-sm border font-mono text-xs"
                    >
                        {{ formatShortcut(shortcut.keys) }}
                    </kbd>
                </li>
            </ul>
        </template>
    </AppDialog>
</template>
