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
    colorFrom: '#ffaa40',
    colorTo: '#9c40ff',
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
        :class="
            cn(
                'border-beam',
                'pointer-events-none absolute inset-0 rounded-[inherit] [border:calc(var(--border-width)*1px)_solid_transparent]',
                '![mask-composite:intersect] ![mask-clip:padding-box,border-box] [mask:linear-gradient(transparent,transparent),linear-gradient(white,white)]',
                'animate-border-beam after:absolute after:aspect-square after:w-[calc(var(--size)*1px)] after:[animation-delay:var(--delay)] after:[background:linear-gradient(to_left,var(--color-from),var(--color-to),transparent)] after:[offset-anchor:calc(var(--anchor)*1%)_50%] after:[offset-path:rect(0_auto_auto_0_round_calc(var(--size)*1px))]',
                props.class,
            )
        "
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
}

.animate-border-beam::after {
    animation: border-beam-anim var(--duration) infinite linear;
}

@keyframes border-beam-anim {
    to {
        offset-distance: 100%;
    }
}
</style>
