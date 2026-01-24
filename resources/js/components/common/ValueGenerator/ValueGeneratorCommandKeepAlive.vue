<script setup lang="ts">
/**
 * @component ValueGeneratorCommandKeepAlive
 * @description State restoration component for maintaining the command context state.
 */
import { useCommand } from '@/components/base/command';
import { useValueGeneratorStore } from '@/stores';
import { onMounted, watch } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppValueGeneratorCommandKeepAliveProps {}

/*
 * Component Setup.
 */

defineProps<AppValueGeneratorCommandKeepAliveProps>();

const store = useValueGeneratorStore();
const { filterState } = useCommand();

onMounted(() => {
    store.restoreCommandState({ filterState });
});

/*
 * Watchers.
 */

watch(
    () => filterState.search,
    newSearch => {
        store.setSearchQuery(newSearch || '');
    },
);
</script>

<!-- This component only handles state restoration, no UI -->
