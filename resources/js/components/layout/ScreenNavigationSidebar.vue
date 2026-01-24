<script setup lang="ts">
/**
 * @component ScreenNavigationSidebar
 * @description The main vertical navigation sidebar for primary application screens.
 */
import AppBrandIcon from '@/components/base/icons/AppBrandIcon.vue';
import {
    AppSidebar,
    AppSidebarContent,
    AppSidebarGroup,
    AppSidebarGroupContent,
    AppSidebarHeader,
    AppSidebarMenu,
    AppSidebarMenuButton,
    AppSidebarMenuItem,
    type SidebarProps,
} from '@/components/base/sidebar';
import {
    BookOpenIcon,
    GithubIcon,
    RadioIcon,
    SettingsIcon,
    TerminalIcon,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';

/*
 * Types & Interfaces.
 */

export interface AppScreenNavigationSidebarProps extends SidebarProps {}

/*
 * Component Setup.
 */

withDefaults(defineProps<AppScreenNavigationSidebarProps>(), {
    collapsible: 'icon',
});

const router = useRouter();
const route = useRoute();

/*
 * Constants.
 */

const coreItems = [
    {
        title: 'HTTP Client',
        route: { name: 'main' },
        icon: TerminalIcon,
        isActive: true,
    },
    {
        title: 'Status',
        route: { name: 'status' },
        icon: RadioIcon,
        isActive: false,
    },
    {
        title: 'Settings',
        route: { name: 'settings' },
        icon: SettingsIcon,
        isActive: false,
    },
];

const externalLinks = [
    {
        title: 'Documentation',
        href: 'https://github.com/sunchayn/nimbus#getting-started',
        icon: BookOpenIcon,
    },
    {
        title: 'GitHub',
        href: 'https://github.com/sunchayn/nimbus',
        icon: GithubIcon,
    },
];

/*
 * Computed & Methods.
 */

const activeItem = computed(() => {
    return coreItems.find(item => item.route.name === route.name) || coreItems[0];
});

const handleExternalClick = (href: string) => {
    window.open(href, '_blank', 'noopener,noreferrer');
};
</script>

<template>
    <!-- We disable collapsible and adjust width to icon. -->
    <!-- This will make the sidebar appear as icons. -->
    <AppSidebar
        collapsible="none"
        class="h-svh !w-[calc(var(--sidebar-width-icon)_+_1px)] border-r"
    >
        <AppSidebarHeader class="h-toolbar flex flex-col items-center">
            <AppBrandIcon />
        </AppSidebarHeader>

        <AppSidebarContent>
            <AppSidebarGroup>
                <AppSidebarGroupContent class="px-0">
                    <AppSidebarMenu>
                        <AppSidebarMenuItem v-for="item in coreItems" :key="item.title">
                            <AppSidebarMenuButton
                                :tooltip="item.title"
                                :is-active="activeItem.title === item.title"
                                class="px-2"
                                @click="() => router.push(item.route)"
                            >
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </AppSidebarMenuButton>
                        </AppSidebarMenuItem>
                    </AppSidebarMenu>
                </AppSidebarGroupContent>
            </AppSidebarGroup>

            <!-- External Links Group -->
            <AppSidebarGroup class="relative mt-auto">
                <AppSidebarGroupContent class="px-0">
                    <AppSidebarMenu>
                        <AppSidebarMenuItem
                            v-for="link in externalLinks"
                            :key="link.title"
                        >
                            <AppSidebarMenuButton
                                :tooltip="link.title"
                                class="px-2"
                                @click="handleExternalClick(link.href)"
                            >
                                <component :is="link.icon" />
                                <span>{{ link.title }}</span>
                            </AppSidebarMenuButton>
                        </AppSidebarMenuItem>
                    </AppSidebarMenu>
                </AppSidebarGroupContent>

                <div class="my-2 h-12 -translate-x-1/2 transform border-r" />
            </AppSidebarGroup>
        </AppSidebarContent>
    </AppSidebar>
</template>
