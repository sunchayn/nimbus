<script setup lang="ts">
import type { NumberDump } from '@/components/domain/Client/Response/ResponseBody/DumpRenderer/index';
import { styles } from '@/components/domain/Client/Response/ResponseBody/DumpRenderer/index';

interface PropsShape {
    dump: NumberDump;
}

defineProps<PropsShape>();
</script>

<template>
    <span :class="styles.value">{{ dump.value }}</span>
</template>
