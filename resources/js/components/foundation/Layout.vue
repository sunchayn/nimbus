<script setup lang="ts">
const spacing = [
    { label: '0.5', value: '2px', class: 'w-0.5' },
    { label: '1', value: '4px', class: 'w-1' },
    { label: '2', value: '8px', class: 'w-2' },
    { label: '3', value: '12px', class: 'w-3' },
    { label: '4', value: '16px', class: 'w-4' },
    { label: '6', value: '24px', class: 'w-6' },
    { label: '8', value: '32px', class: 'w-8' },
    { label: '12', value: '48px', class: 'w-12' },
    { label: '16', value: '64px', class: 'w-16' },
];

const radii = [
    { label: 'sm', variable: 'calc(var(--radius) - 4px)', style: { borderRadius: 'calc(var(--radius) - 4px)' } },
    { label: 'md', variable: 'calc(var(--radius) - 2px)', style: { borderRadius: 'calc(var(--radius) - 2px)' } },
    { label: 'lg', variable: 'var(--radius)', style: { borderRadius: 'var(--radius)' } },
];

const layoutVars = [
    { name: 'Toolbar Height', value: '40px', variable: '--toolbar-height' },
    { name: 'Sub-Toolbar Height', value: '34px', variable: '--sub-toolbar-height' },
    { name: 'Header Height', value: '60px', variable: '--header-height' },
    { name: 'Sidebar Width', value: '280px', variable: '--sidebar-width' },
];
</script>

<template>
    <div class="p-8 space-y-12 bg-background text-foreground min-h-screen">
        <section>
            <h2 class="text-2xl font-bold mb-6 border-b pb-2">Spacing Scale (Tailwind 4)</h2>
            <div class="space-y-4">
                <div 
                    v-for="s in spacing" 
                    :key="s.label"
                    class="flex items-center gap-4"
                >
                    <div class="w-12 text-xs font-mono text-muted-foreground">{{ s.label }}</div>
                    <div class="w-12 text-xs font-mono text-muted-foreground">{{ s.value }}</div>
                    <div :class="s.class" class="h-4 bg-primary rounded-sm shadow-sm" />
                </div>
            </div>
        </section>

        <section>
            <h2 class="text-2xl font-bold mb-6 border-b pb-2">Border Radii</h2>
            <div class="flex flex-wrap gap-8">
                <div 
                    v-for="r in radii" 
                    :key="r.label"
                    class="space-y-2 text-center"
                >
                    <div 
                        class="w-24 h-24 bg-muted border-2 border-primary/20 flex items-center justify-center font-bold"
                        :style="r.style"
                    >
                        {{ r.label }}
                    </div>
                    <code class="text-[10px] block opacity-70">{{ r.variable }}</code>
                </div>
            </div>
        </section>

        <section>
            <h2 class="text-2xl font-bold mb-6 border-b pb-2">Layout Constants</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div 
                    v-for="v in layoutVars" 
                    :key="v.name"
                    class="flex justify-between items-center p-4 bg-muted/30 rounded-lg border"
                >
                    <div>
                        <span class="block font-bold text-sm">{{ v.name }}</span>
                        <code class="text-xs text-muted-foreground">{{ v.variable }}</code>
                    </div>
                    <div class="text-lg font-mono font-bold">{{ v.value }}</div>
                </div>
            </div>
        </section>
    </div>
</template>
