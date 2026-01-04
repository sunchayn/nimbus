<script setup lang="ts">
import {
    StringDump,
    styles,
} from '@/components/domain/Client/Response/ResponseBody/DumpRenderer/index';

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
