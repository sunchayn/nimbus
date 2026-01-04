<script setup lang="ts">
import { styles } from '@/components/domain/Client/Response/ResponseBody/DumpRenderer/index';

withDefaults(defineProps<{ keyName: string | number; numerical?: boolean }>(), {
    numerical: false,
});
</script>

<template>
    <span v-if="keyName && !numerical" :class="styles.key">"{{ keyName }}":&nbsp;</span>
    <span v-else-if="keyName" :class="styles.numericalKey">
        {{ String(keyName) }}:&nbsp;
    </span>
</template>
