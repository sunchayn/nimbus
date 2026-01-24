<script setup lang="ts">
import type { ObjectDumpProperty } from '@/components/domain/Client/Response/ResponseBody/DumpRenderer/index';
import { styles } from '@/components/domain/Client/Response/ResponseBody/DumpRenderer/index';
import { cn } from '@/utils';

interface PropsShape {
    property: ObjectDumpProperty;
    keyName: string;
}

defineProps<PropsShape>();

const visibilityColor = (visibility: 'public' | 'protected' | 'private') => {
    return {
        public: 'text-emerald-600 dark:text-emerald-500',
        protected: 'text-amber-600 dark:text-amber-500',
        private: 'text-zinc-500 dark:text-zinc-400',
    }[visibility];
};

const visibilitySymbol = (visibility: 'public' | 'protected' | 'private') => {
    return {
        public: '+',
        protected: '#',
        private: '-',
    }[visibility];
};
</script>

<template>
    <span :class="styles.key">
        <span :class="cn('text-xs', visibilityColor(property.visibility))">
            {{ visibilitySymbol(property.visibility) }}
        </span>
        <span :class="styles.objectProperty">{{ keyName }}:</span>
    </span>
</template>
