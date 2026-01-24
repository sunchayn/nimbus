<script setup lang="ts">
/**
 * @component RoutesListItem
 * @description An individual list item representing a route definition.
 */
import { AppSidebarMenuButton } from '@/components/base/sidebar';
import HttpVerbLabel from '@/components/domain/HttpVerbLabel/HttpVerbLabel.vue';
import { type RouteDefinition } from '@/interfaces/routes/routes';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppRoutesListItemProps {
    route: RouteDefinition;
    resource: string;
    isActive: boolean;
    onClick?: () => void;
}

export interface AppRoutesListItemEmits {
    (e: 'click'): void;
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<AppRoutesListItemProps>(), {
    isActive: false,
    onClick: () => {},
});

const emit = defineEmits<AppRoutesListItemEmits>();

/*
 * Computed & Methods.
 */

const endpointsSegments = computed(() => {
    const segments = props.route.shortEndpoint
        .replace(`${props.resource}`, '')
        .split('/');

    if (segments.length > 1 && segments[0] === '') {
        segments.shift();
    }

    return segments.map(segment => {
        if (!segment.startsWith('{')) {
            return {
                value: `/${segment}`,
                isRouteVariable: false,
            };
        }

        return {
            value: `/${segment}`,
            isRouteVariable: true,
        };
    });
});

const handleClick = () => {
    if (props.onClick) {
        props.onClick();
    }

    emit('click');
};
</script>

<template>
    <AppSidebarMenuButton
        :is-active="isActive"
        class="text-sm data-[active=true]:rounded-l-none"
        @click="handleClick"
    >
        <HttpVerbLabel :method="route.method" />
        <span class="whitespace-nowrap">
            <template v-for="(segment, index) in endpointsSegments" :key="index">
                <span v-if="!segment.isRouteVariable">{{ segment.value }}</span>
                <span v-else>
                    <span class="text-muted-foreground">{{ segment.value }}</span>
                </span>
            </template>
        </span>
    </AppSidebarMenuButton>
</template>
