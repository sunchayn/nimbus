<script setup lang="ts">
/**
 * @component AppDialog
 * @description A modal dialog built on Nuxt UI. The default slot is the trigger.
 */
import { useForwardedProps } from '@/components/base/useForwardedProps';
import type { ModalProps, ModalSlots } from '@nuxt/ui/components/Modal.vue';
import UModal from '@nuxt/ui/components/Modal.vue';

/*
 * Types & Interfaces.
 */

export interface AppDialogProps extends ModalProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppDialogProps>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    enter: [];
    leave: [];
    'after:enter': [];
    'after:leave': [];
    'close:prevent': [];
}>();

defineSlots<ModalSlots>();

const forwarded = useForwardedProps(props);
</script>

<template>
    <UModal
        v-bind="forwarded"
        @update:open="value => emit('update:open', value)"
        @enter="emit('enter')"
        @leave="emit('leave')"
        @after:enter="emit('after:enter')"
        @after:leave="emit('after:leave')"
        @close:prevent="emit('close:prevent')"
    >
        <template v-for="(_, name) in $slots" :key="name" #[name]="slotProps">
            <slot :name="name as never" v-bind="slotProps ?? {}" />
        </template>
    </UModal>
</template>
