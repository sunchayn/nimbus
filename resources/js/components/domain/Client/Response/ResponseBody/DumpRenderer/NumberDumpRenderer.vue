<script setup lang="ts">
import {
    NumberDump,
    styles,
} from '@/components/domain/Client/Response/ResponseBody/DumpRenderer/index';

interface PropsShape {
    dump: NumberDump;
}

defineProps<PropsShape>();
</script>

<template>
    <span :class="styles.value">{{ dump.value }}</span>
</template>
