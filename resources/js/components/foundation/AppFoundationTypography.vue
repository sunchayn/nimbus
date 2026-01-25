<script setup lang="ts">
defineOptions({
    name: 'AppFoundationTypography',
});
const fontSizes = [
    { name: 'xxs', size: '0.5625rem / 9px', class: 'text-xxs' },
    { name: 'xs', size: '0.75rem / 12px', class: 'text-xs' },
    { name: 'sm', size: '0.875rem / 14px', class: 'text-sm' },
    { name: 'base', size: '1rem / 16px', class: 'text-base' },
    { name: 'lg', size: '1.125rem / 18px', class: 'text-lg' },
    { name: 'xl', size: '1.25rem / 20px', class: 'text-xl' },
    { name: '2xl', size: '1.5rem / 24px', class: 'text-2xl' },
    { name: '3xl', size: '1.875rem / 30px', class: 'text-3xl' },
];

const fontFamilies = [
    { name: 'Sans (UI)', variable: '--font-sans', class: 'font-sans' },
    { name: 'Serif', variable: '--font-serif', class: 'font-serif' },
    { name: 'Mono', variable: '--font-mono', class: 'font-mono' },
];
</script>

<template>
    <div class="bg-background text-foreground min-h-screen space-y-12 p-8">
        <section>
            <h2 class="mb-6 border-b pb-2 text-2xl font-bold">Font Scale</h2>
            <div class="space-y-8">
                <div
                    v-for="fs in fontSizes"
                    :key="fs.name"
                    class="border-subtle flex items-baseline gap-8 border-b pb-4 last:border-0"
                >
                    <div class="w-32 flex-shrink-0">
                        <span class="block text-sm font-bold">{{ fs.name }}</span>
                        <code class="text-subtle-foreground text-[10px]">
                            {{ fs.size }}
                        </code>
                    </div>
                    <div :class="fs.class" class="flex-grow truncate">
                        The quick brown fox jumps over the lazy dog.
                    </div>
                </div>
            </div>
        </section>

        <section>
            <h2 class="mb-6 border-b pb-2 text-2xl font-bold">Font Families</h2>
            <div class="space-y-8">
                <div v-for="font in fontFamilies" :key="font.name" class="space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="font-bold">{{ font.name }}</span>
                        <code class="text-subtle-foreground text-xs">
                            {{ font.variable }}
                        </code>
                    </div>
                    <div :class="font.class" class="bg-subtle/30 rounded-lg p-4 text-xl">
                        Pack my box with five dozen liquor jugs. 1234567890 !@#$%^&*()
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
