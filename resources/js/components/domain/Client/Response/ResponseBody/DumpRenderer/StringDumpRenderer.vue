<script setup lang="ts">
/**
 * @component StringDumpRenderer
 * @description Renders a quoted string value and its length in a dump.
 */
import type { StringDump } from '@/components/domain/Client/Response/ResponseBody/DumpRenderer/index';
import { styles } from '@/components/domain/Client/Response/ResponseBody/DumpRenderer/index';

interface PropsShape {
    dump: StringDump;
}

defineProps<PropsShape>();
</script>

<template>
    <span :class="styles.stringValue">
        "{{ dump.value }}"
        <span :class="styles.meta">({{ dump.value.length }})</span>
    </span>
</template>
