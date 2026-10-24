<script setup lang="ts">
/**
 * @component ObjectDumpValuePropertyKey
 * @description Renders an object property name with its visibility symbol in a dump.
 */
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
        public: 'text-success',
        protected: 'text-warning',
        private: 'text-muted',
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
