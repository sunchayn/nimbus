<script setup lang="ts">
import {
    ConstDump,
    styles,
} from '@/components/domain/Client/Response/ResponseBody/DumpRenderer/index';
import { cn } from '@/utils';

interface PropsShape {
    dump: ConstDump;
}

defineProps<PropsShape>();
</script>

<template>
    <span :class="cn(styles.value, 'italic')">
        {{ String(dump.value) }}
    </span>
</template>
