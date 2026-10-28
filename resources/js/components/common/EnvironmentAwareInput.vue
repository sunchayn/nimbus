<script setup lang="ts">
/**
 * @component EnvironmentAwareInput
 * @description A high-fidelity input component that supports environment variable highlighting
 * and individual tooltips for multiple env variable segments using a dual-layer mirrored approach.
 */
import type { AppInputProps } from '@/components/base/input';
import { EnvVariableSegment } from '@/components/common/EnvVariableSegment';
import GeneratorInput from '@/components/common/GeneratorInput.vue';
import { useEnvVariablesAwareString } from '@/composables/request/useEnvVariablesAwareString';
import { useTabHorizontalScroll } from '@/composables/ui/useTabHorizontalScroll';
import { EnvVariableCheckStatus } from '@/interfaces/common/env-vars';
import { cn, withOccurrenceKeys } from '@/utils/ui';
import { computed, nextTick, onMounted, shallowRef, useTemplateRef, watch } from 'vue';

defineOptions({
    inheritAttrs: false,
});

/*
 * Types & Interfaces.
 */

export interface Props {
    placeholder?: string;
    disabled?: boolean;
    class?: string;
    inputClass?: string;
    variant?: AppInputProps['variant'];
    size?: AppInputProps['size'];
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<Props>(), {
    placeholder: '',
    disabled: false,
    class: '',
    inputClass: '',
    variant: 'outline',
    size: 'md',
});

const modelValue = defineModel<string>({ required: true });

/*
 * State.
 */

const internalRawValue = shallowRef('');
const isInputActive = shallowRef(false);
const activelyHoveredEnvVariableSegment = shallowRef<number | null>(null);
const inputRef = useTemplateRef<InstanceType<typeof GeneratorInput>>('inputRef');
const mirrorRef = useTemplateRef<HTMLDivElement>('mirrorRef');

const delegatedProps = computed(() => {
    const { class: _, inputClass: __, ...rest } = props;

    return rest;
});

/*
 * Composables.
 */

const { scrollContainer, showLeftMask, showRightMask, updateScrollMasks } =
    useTabHorizontalScroll({
        SCROLL_THRESHOLD: 4,
    });

const { raw: rawValue, segments } = useEnvVariablesAwareString(modelValue);

const keyedSegments = computed(() =>
    withOccurrenceKeys(segments.value, segment => segment.text),
);

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
    const el = inputRef.value?.inputRef;

    if (!el) {
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

// Inbound sync from the parent to the local input.
watch(
    rawValue,
    (newValue: string) => {
        if (newValue !== internalRawValue.value && !isInputActive.value) {
            internalRawValue.value = newValue;
        }
    },
    { immediate: true },
);

// Outbound sync from the local input to the parent.
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
    if (inputRef.value?.inputRef) {
        scrollContainer.value = inputRef.value.inputRef;

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
        <GeneratorInput
            ref="inputRef"
            v-model="internalRawValue"
            v-bind="{ ...delegatedProps, ...$attrs }"
            :variant="variant"
            class="z-sticky relative h-full flex-1"
            :ui="{
                base: cn(
                    'h-full flex-1 bg-transparent text-transparent caret-default',
                    inputClass,
                ),
            }"
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
                    'pointer-events-none absolute inset-0 flex items-center overflow-hidden whitespace-pre',
                    size === 'sm' ? 'px-control-sm text-xs' : 'px-control-md text-sm',
                    inputClass,
                )
            "
        >
            <template v-for="({ item: segment, key }, index) in keyedSegments" :key="key">
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
                            'bg-error/15 text-error':
                                segment.status === EnvVariableCheckStatus.Missing,
                            'bg-warning/15 text-warning':
                                segment.status === EnvVariableCheckStatus.Empty,
                            'bg-inverted/20 text-highlighted':
                                segment.status === EnvVariableCheckStatus.Resolved,
                        }"
                    >
                        {{ segment.text }}
                    </span>
                </EnvVariableSegment>
                <span
                    v-else
                    class="text-default inline leading-none"
                    :data-segment-index="index"
                >
                    {{ segment.text }}
                </span>
            </template>
        </div>

        <!-- Scroll Gradient Masks -->
        <div
            v-show="showLeftMask"
            class="from-default z-raised w-fade duration-base pointer-events-none absolute top-0 bottom-0 left-0 bg-gradient-to-r to-transparent transition-opacity"
        />
        <div
            v-show="showRightMask"
            class="from-default z-raised w-fade duration-base pointer-events-none absolute top-0 right-0 bottom-0 bg-gradient-to-l to-transparent transition-opacity"
        />
    </div>
</template>
