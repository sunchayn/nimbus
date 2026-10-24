<script setup lang="ts">
/**
 * @component AppBorderBeam
 * @description An animated beam of light that travels around a container's border.
 */
import { cn } from '@/utils/ui';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppBorderBeamProps {
    class?: string;
    size?: number;
    duration?: number;
    borderWidth?: number;
    anchor?: number;
    colorFrom?: string;
    colorTo?: string;
    delay?: number;
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<AppBorderBeamProps>(), {
    class: '',
    size: 200,
    duration: 15000,
    anchor: 10,
    borderWidth: 1.5,
    colorFrom: 'var(--beam-from)',
    colorTo: 'var(--beam-to)',
    delay: 0,
});

/*
 * Computed & Methods.
 */

const durationInSeconds = computed(() => `${props.duration}s`);
const delayInSeconds = computed(() => `${props.delay}s`);
</script>

<template>
    <div
        :class="cn('border-beam pointer-events-none absolute inset-0', props.class)"
    ></div>
</template>

<style scoped>
.border-beam {
    --size: v-bind(size);
    --duration: v-bind(durationInSeconds);
    --anchor: v-bind(anchor);
    --border-width: v-bind(borderWidth);
    --color-from: v-bind(colorFrom);
    --color-to: v-bind(colorTo);
    --delay: v-bind(delayInSeconds);

    border: calc(var(--border-width) * 1px) solid transparent;
    border-radius: inherit;

    /*
     * The mask only cuts the beam to the border ring.
     * Its colors never render, so they are not theme colors.
     */
    mask: linear-gradient(transparent, transparent), linear-gradient(white, white);
    mask-clip: padding-box, border-box;
    mask-composite: intersect;
}

.border-beam::after {
    content: '';
    position: absolute;
    aspect-ratio: 1;
    width: calc(var(--size) * 1px);
    background: linear-gradient(to left, var(--color-from), var(--color-to), transparent);
    offset-anchor: calc(var(--anchor) * 1%) 50%;
    offset-path: rect(0 auto auto 0 round calc(var(--size) * 1px));
    animation: border-beam-anim var(--duration) infinite linear;
    animation-delay: var(--delay);
}

@keyframes border-beam-anim {
    to {
        offset-distance: 100%;
    }
}

/* The beam is decorative, so it is hidden when the user prefers less motion. */
@media (prefers-reduced-motion: reduce) {
    .border-beam::after {
        animation: none;
        display: none;
    }
}
</style>
