<script setup lang="ts">
/**
 * @component EnvironmentAwareInput
 * @description A high-fidelity input component that supports environment variable highlighting
 * and individual tooltips for multiple env variable segments using a dual-layer mirrored approach.
 */
import { AppInput, inputVariants, type InputVariants } from '@/components/base/input';
import { EnvVariableSegment } from '@/components/common/EnvVariableSegment';
import { useEnvVariablesAwareString } from '@/composables/request/useEnvVariablesAwareString';
import { useTabHorizontalScroll } from '@/composables/ui/useTabHorizontalScroll';
import { EnvVariableCheckStatus } from '@/interfaces/common/env-vars';
import { cn } from '@/utils/ui';
import {
    computed,
    nextTick,
    onMounted,
    ref,
    watch,
    type ComponentPublicInstance,
} from 'vue';

defineOptions({
    inheritAttrs: false,
});

/*
 * Types & Interfaces.
 */

export interface Props {
    modelValue: string;
    placeholder?: string;
    disabled?: boolean;
    class?: string;
    inputClass?: string;
    variant?: InputVariants['variant'];
}

export interface AppEnvironmentAwareInputEmits {
    (event: 'update:modelValue', value: string): void;
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<Props>(), {
    placeholder: '',
    disabled: false,
    class: '',
    inputClass: '',
    variant: 'default',
});

const emit = defineEmits<AppEnvironmentAwareInputEmits>();

/*
 * State.
 */

const internalRawValue = ref('');
const isInputActive = ref(false);
const activelyHoveredEnvVariableSegment = ref<number | null>(null);
const inputRef = ref<ComponentPublicInstance | HTMLElement | null>(null);
const mirrorRef = ref<HTMLDivElement | null>(null);

/**
 * We wrap the parent's modelValue in a proxy computed that useEnvVariablesAwareString will manage.
 */
const parentSourceProxy = computed({
    get: () => props.modelValue,
    set: (value: string) => emit('update:modelValue', value),
});

const delegatedProps = computed(() => {
    /**
     * We MUST filter out modelValue from the delegated props.
     * If we don't, AppInput will emit raw string updates directly to the parent,
     * bypassing our useEnvVariablesAwareString logic.
     */
    const { class: _, inputClass: __, modelValue: ___, ...rest } = props;

    return rest;
});

/*
 * Composables.
 */

const { scrollContainer, showLeftMask, showRightMask, updateScrollMasks } =
    useTabHorizontalScroll({
        SCROLL_THRESHOLD: 4,
    });

const { raw: rawValue, segments } = useEnvVariablesAwareString(parentSourceProxy);

/*
 * Actions.
 */

const handleScroll = (event: Event) => {
    updateScrollMasks();
    if (mirrorRef.value && event.target instanceof HTMLInputElement) {
        mirrorRef.value.scrollLeft = event.target.scrollLeft;
    }
};

/**
 * Detects which rich-text segment is being hovered through the transparent input.
 */
const handleMouseMove = (event: MouseEvent) => {
    const node = inputRef.value;
    const el = node && '$el' in node ? (node.$el as HTMLElement) : (node as HTMLElement);

    if (!el || !(el instanceof HTMLElement)) {
        return;
    }

    el.style.pointerEvents = 'none';
    if (mirrorRef.value) {
        mirrorRef.value.style.pointerEvents = 'auto';
    }

    const matchingElementFromMirrorLayer = document.elementFromPoint(
        event.clientX,
        event.clientY,
    );

    el.style.pointerEvents = 'auto';
    if (mirrorRef.value) {
        mirrorRef.value.style.pointerEvents = 'none';
    }

    const segmentSpan = matchingElementFromMirrorLayer?.closest('[data-segment-index]');
    if (segmentSpan) {
        activelyHoveredEnvVariableSegment.value = parseInt(
            segmentSpan.getAttribute('data-segment-index') || '-1',
        );

        return;
    }

    handleMouseLeave();
};

const handleMouseLeave = () => {
    activelyHoveredEnvVariableSegment.value = null;
};

/*
 * Watchers.
 */

// Inbound sync: Parent -> Local Input
watch(
    rawValue,
    (newValue: string) => {
        if (newValue !== internalRawValue.value && !isInputActive.value) {
            internalRawValue.value = newValue;
        }
    },
    { immediate: true },
);

// Outbound sync: Local Input -> Parent
watch(internalRawValue, (newValue: string) => {
    if (newValue !== rawValue.value) {
        rawValue.value = newValue;
    }
});

watch(internalRawValue, () => {
    nextTick(() => {
        updateScrollMasks();
    });
});

/*
 * Lifecycle.
 */

onMounted(() => {
    if (inputRef.value) {
        const node = inputRef.value;
        scrollContainer.value =
            node && '$el' in node ? (node.$el as HTMLElement) : (node as HTMLElement);

        updateScrollMasks();
    }
});

/*
 * Misc.
 */

defineExpose({
    activelyHoveredEnvVariableSegment,
    mirrorRef,
});
</script>

<template>
    <div :class="cn('relative flex h-full min-w-0 flex-1 items-stretch', props.class)">
        <!-- Actual Input (Transparent text) - TOP LAYER -->
        <AppInput
            ref="inputRef"
            v-model="internalRawValue"
            v-bind="{ ...delegatedProps, ...$attrs }"
            :variant="variant"
            :class="
                cn(
                    'caret-foreground relative z-10 h-full flex-1 bg-transparent text-transparent',
                    inputClass,
                )
            "
            spellcheck="false"
            @scroll="handleScroll"
            @mousemove="handleMouseMove"
            @mouseleave="handleMouseLeave"
            @input="handleScroll"
            @focus="() => (isInputActive = true)"
            @blur="() => (isInputActive = false)"
        />

        <!-- Mirrored Background for Rich Display - BOTTOM LAYER -->
        <div
            ref="mirrorRef"
            :class="
                cn(
                    'pointer-events-none absolute inset-0 z-0 flex items-center overflow-hidden whitespace-pre',
                    inputVariants({ variant }),
                    inputClass,
                )
            "
        >
            <template v-for="(segment, index) in segments" :key="index">
                <EnvVariableSegment
                    v-if="segment.isEnvVariable"
                    :status="segment.status || EnvVariableCheckStatus.None"
                    :variable-value="segment.resolvedValue ?? undefined"
                    :is-open="activelyHoveredEnvVariableSegment === index"
                >
                    <span
                        :data-segment-index="index"
                        class="pointer-events-auto inline leading-none transition-colors"
                        :class="{
                            'bg-destructive/15 text-destructive':
                                segment.status === EnvVariableCheckStatus.Missing,
                            'bg-warning/15 text-warning':
                                segment.status === EnvVariableCheckStatus.Empty,
                            'bg-primary/20 text-primary':
                                segment.status === EnvVariableCheckStatus.Resolved,
                        }"
                    >
                        {{ segment.text }}
                    </span>
                </EnvVariableSegment>
                <span
                    v-else
                    class="text-foreground inline leading-none"
                    :data-segment-index="index"
                >
                    {{ segment.text }}
                </span>
            </template>
        </div>

        <!-- Scroll Gradient Masks -->
        <div
            v-show="showLeftMask"
            class="from-background pointer-events-none absolute top-0 bottom-0 left-0 z-20 w-8 bg-gradient-to-r to-transparent transition-opacity duration-200"
        />
        <div
            v-show="showRightMask"
            class="from-background pointer-events-none absolute top-0 right-0 bottom-0 z-20 w-8 bg-gradient-to-l to-transparent transition-opacity duration-200"
        />
    </div>
</template>
