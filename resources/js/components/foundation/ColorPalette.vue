<script setup lang="ts">
const semanticColors = [
    { name: 'Background', variable: '--color-background' },
    { name: 'Foreground', variable: '--color-foreground' },
    { name: 'Primary', variable: '--color-primary', foreground: '--color-primary-foreground' },
    { name: 'Secondary', variable: '--color-secondary', foreground: '--color-secondary-foreground' },
    { name: 'Accent', variable: '--color-accent', foreground: '--color-accent-foreground' },
    { name: 'Destructive', variable: '--color-destructive', foreground: '--color-destructive-foreground' },
    { name: 'Muted', variable: '--color-muted', foreground: '--color-muted-foreground' },
    { name: 'Card', variable: '--color-card', foreground: '--color-card-foreground' },
    { name: 'Popover', variable: '--color-popover', foreground: '--color-popover-foreground' },
    { name: 'Border', variable: '--color-border' },
    { name: 'Input', variable: '--color-input' },
    { name: 'Ring', variable: '--color-ring' },
];

const statusColors = [
    { name: 'Success', variable: '--color-success', foreground: '--color-success-foreground' },
    { name: 'Warning', variable: '--color-warning', foreground: '--color-warning-foreground' },
    { name: 'Info', variable: '--color-info', foreground: '--color-info-foreground' },
];

const sidebarColors = [
    { name: 'Sidebar BG', variable: '--sidebar-background' },
    { name: 'Sidebar FG', variable: '--sidebar-foreground' },
    { name: 'Sidebar Primary', variable: '--sidebar-primary', foreground: '--sidebar-primary-foreground' },
    { name: 'Sidebar Accent', variable: '--sidebar-accent', foreground: '--sidebar-accent-foreground' },
    { name: 'Sidebar Border', variable: '--sidebar-border' },
];
</script>

<template>
    <div class="p-8 space-y-12 bg-background text-foreground min-h-screen">
        <section>
            <h2 class="text-2xl font-bold mb-6 border-b pb-2">Semantic Palette</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <div 
                    v-for="color in semanticColors" 
                    :key="color.name"
                    class="space-y-2"
                >
                    <div 
                        class="h-24 rounded-lg border shadow-sm flex flex-col items-center justify-center p-4 text-center"
                        :style="{ 
                            backgroundColor: `var(${color.variable})`,
                            color: color.foreground ? `var(${color.foreground})` : 'inherit',
                            borderColor: 'var(--color-border)'
                        }"
                    >
                        <span class="font-bold text-sm">{{ color.name }}</span>
                        <code class="text-[10px] opacity-70 mt-1">{{ color.variable }}</code>
                    </div>
                </div>
            </div>
        </section>

        <section>
            <h2 class="text-2xl font-bold mb-6 border-b pb-2">Status Colors</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <div 
                    v-for="color in statusColors" 
                    :key="color.name"
                    class="space-y-2"
                >
                    <div 
                        class="h-24 rounded-lg border shadow-sm flex flex-col items-center justify-center p-4 text-center"
                        :style="{ 
                            backgroundColor: `var(${color.variable})`,
                            color: color.foreground ? `var(${color.foreground})` : 'inherit'
                        }"
                    >
                        <span class="font-bold text-sm">{{ color.name }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section>
            <h2 class="text-2xl font-bold mb-6 border-b pb-2">Sidebar System</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <div 
                    v-for="color in sidebarColors" 
                    :key="color.name"
                    class="space-y-2"
                >
                    <div 
                        class="h-24 rounded-lg border shadow-sm flex flex-col items-center justify-center p-4 text-center"
                        :style="{ 
                            backgroundColor: `var(${color.variable})`,
                            color: color.foreground ? `var(${color.foreground})` : 'inherit'
                        }"
                    >
                        <span class="font-bold text-sm">{{ color.name }}</span>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
