<script setup lang="ts">
/**
 * @component TimeTravelDialog
 * @description A dialog to set the time the target application believes it is when a request runs.
 * The time is either a relative amount from now or an absolute date and time.
 */
import { AppButton } from '@/components/base/button';
import { AppDialog } from '@/components/base/dialog';
import { AppIcon } from '@/components/base/icons';
import { AppInput } from '@/components/base/input';
import { AppTabs } from '@/components/base/tabs';
import {
    TIME_TRAVEL_PRESETS,
    applyRelativeTimeTravel,
    formatPreviewDate,
    formatRelativeTimeTravel,
    parseAbsoluteTimeTravel,
    parseRelativeTimeTravel,
    type TimeTravelPreset,
} from '@/utils/request/timeTravel';
import { computed, shallowRef, watch } from 'vue';

/*
 * Types & Interfaces.
 */

interface TimeTravelDialogProps {
    open: boolean;
    value: string;
}

type Mode = 'relative' | 'absolute';

/*
 * Component Setup.
 */

const props = defineProps<TimeTravelDialogProps>();
const emits = defineEmits<{
    'update:open': [value: boolean];
    save: [value: string];
}>();

const modes = [
    { label: 'Relative to now', value: 'relative', icon: 'i-hugeicons-time-quarter' },
    { label: 'Exact date and time', value: 'absolute', icon: 'i-hugeicons-calendar-03' },
];

/*
 * State.
 */

const mode = shallowRef<Mode>('relative');
const relativeText = shallowRef('+1 day');
const absolute = shallowRef('');
const unsupportedValue = shallowRef('');

// Start from the saved value each time the dialog opens.
watch(
    () => props.open,
    isOpen => {
        if (isOpen) {
            loadValue(props.value.trim());
        }
    },
    { immediate: true },
);

/*
 * Computed & Methods.
 */

function loadValue(value: string) {
    const parsed = parseRelativeTimeTravel(value);
    const exact = parseAbsoluteTimeTravel(value);

    unsupportedValue.value = value !== '' && !parsed && !exact ? value : '';
    mode.value = exact ? 'absolute' : 'relative';
    absolute.value = exact ?? '';
    relativeText.value = parsed ? formatRelativeTimeTravel(parsed) : '+1 day';
}

const relativeParts = computed(() => parseRelativeTimeTravel(relativeText.value));

const absoluteValue = computed(() => parseAbsoluteTimeTravel(absolute.value));

const result = computed(() => {
    if (mode.value === 'relative') {
        return relativeParts.value ? formatRelativeTimeTravel(relativeParts.value) : null;
    }

    return absoluteValue.value;
});

const preview = computed(() => {
    if (mode.value === 'relative') {
        return relativeParts.value
            ? formatPreviewDate(applyRelativeTimeTravel(relativeParts.value))
            : null;
    }

    return absoluteValue.value ? formatPreviewDate(new Date(absoluteValue.value)) : null;
});

const errorMessage = computed(() => {
    if (result.value) {
        return '';
    }

    if (mode.value === 'absolute') {
        return 'Pick a date and time.';
    }

    return relativeText.value.trim() === ''
        ? 'Type a time such as +1h or -2d.'
        : 'Not valid. Try +1h, -2d or +1w 3d.';
});

const isActivePreset = (preset: TimeTravelPreset) =>
    mode.value === 'relative' &&
    relativeParts.value !== null &&
    formatRelativeTimeTravel(relativeParts.value) === preset.value;

const applyPreset = (preset: TimeTravelPreset) => {
    mode.value = 'relative';
    relativeText.value = preset.label;
};

const save = (value: string) => {
    emits('save', value);
    emits('update:open', false);
};

const apply = () => {
    if (result.value) {
        save(result.value);
    }
};
</script>

<template>
    <AppDialog
        :open="open"
        description="Choose the time your application should see when this request runs."
        :ui="{ content: 'max-w-dialog-lg' }"
        @update:open="emits('update:open', $event)"
    >
        <template #title>
            <AppIcon name="i-hugeicons-clock-01" class="size-icon-md" />
            Set current time
        </template>

        <template #body>
            <div class="gap-loose flex flex-col" data-testid="time-travel-dialog">
                <AppTabs
                    v-model="mode"
                    :items="modes"
                    :content="false"
                    variant="pill"
                    color="neutral"
                    class="w-full"
                    :ui="{
                        list: 'bg-muted p-tight w-full',
                        trigger: 'text-muted data-[state=active]:text-highlighted flex-1',
                        indicator: 'bg-default border-inverted border shadow-none',
                    }"
                    data-testid="time-travel-mode"
                />

                <p
                    v-if="unsupportedValue"
                    class="text-warning text-xs"
                    data-testid="time-travel-unsupported"
                >
                    The current value "{{ unsupportedValue }}" is not supported here.
                    Applying a new time replaces it.
                </p>

                <div
                    v-if="mode === 'relative'"
                    class="gap-loose flex flex-col"
                    data-testid="time-travel-relative"
                >
                    <div class="gap-base flex flex-col">
                        <h4 class="text-default text-xs font-medium">Quick picks</h4>
                        <div class="gap-tight flex flex-nowrap">
                            <AppButton
                                v-for="(preset, index) in TIME_TRAVEL_PRESETS"
                                :key="preset.label"
                                size="xs"
                                class="font-mono"
                                :variant="isActivePreset(preset) ? 'subtle' : 'outline'"
                                :color="isActivePreset(preset) ? 'primary' : 'neutral'"
                                :label="preset.label"
                                :title="preset.description"
                                :aria-label="preset.description"
                                :data-testid="`time-travel-preset-${index}`"
                                @click="applyPreset(preset)"
                            />
                        </div>
                    </div>

                    <div class="gap-base flex flex-col">
                        <AppInput
                            v-model="relativeText"
                            size="sm"
                            class="w-40"
                            placeholder="+1h, -2d, +1w 3d"
                            :color="relativeParts ? undefined : 'error'"
                            aria-label="Relative time"
                            data-testid="time-travel-relative-input"
                            @keydown.enter="apply"
                        />
                        <p class="text-muted text-xs">
                            Use s, m, h, d, w, mo or y for the unit. Combine parts, such
                            as +1d 2h 30m.
                        </p>
                    </div>
                </div>

                <div
                    v-else
                    class="gap-base flex flex-col"
                    data-testid="time-travel-absolute"
                >
                    <h4 class="text-default text-xs font-medium">Date and time</h4>
                    <AppInput
                        v-model="absolute"
                        type="datetime-local"
                        size="sm"
                        aria-label="Exact date and time"
                        data-testid="time-travel-picker"
                        @keydown.enter="apply"
                    />
                </div>

                <div
                    class="bg-muted border-default p-card gap-tight flex flex-col rounded-md border text-xs"
                    aria-live="polite"
                    data-testid="time-travel-preview"
                >
                    <span class="text-muted">The application clock will read</span>
                    <span
                        :class="preview ? 'text-default font-medium' : 'text-error'"
                        data-testid="time-travel-preview-value"
                    >
                        {{ preview ?? errorMessage }}
                    </span>
                    <span v-if="mode === 'relative'" class="text-muted">
                        Counted from the moment the request runs. Shown in your browser
                        time.
                    </span>
                </div>
            </div>
        </template>

        <template #footer>
            <div class="gap-base flex w-full items-center justify-between">
                <AppButton
                    size="sm"
                    variant="outline"
                    color="neutral"
                    label="Use now"
                    title="Remove the fixed time and use the real time"
                    :disabled="!value"
                    data-testid="time-travel-use-now"
                    @click="save('')"
                />
                <div class="gap-base flex">
                    <AppButton
                        size="sm"
                        variant="outline"
                        color="neutral"
                        label="Cancel"
                        @click="emits('update:open', false)"
                    />
                    <AppButton
                        size="sm"
                        label="Apply"
                        :disabled="!result"
                        data-testid="time-travel-apply"
                        @click="apply"
                    />
                </div>
            </div>
        </template>
    </AppDialog>
</template>
