<script setup lang="ts">
/**
 * @component SingleDumpRenderer
 * @description Recursively renders a single dump value, handling different types (objects, arrays, strings, etc.).
 */
import {
    AppCollapsible,
    AppCollapsibleContent,
    AppCollapsibleTrigger,
} from '@/components/base/collapsible';
import DumpKeyRenderer from '@/components/domain/Client/Response/ResponseBody/DumpRenderer/DumpKeyRenderer.vue';
import {
    type ArrayDump,
    type ClosureDump,
    type ConstDump,
    ConstDumpRenderer,
    type DumpValue,
    type NumberDump,
    NumberDumpRenderer,
    type ObjectDump,
    type ObjectDumpProperty,
    type StringDump,
    StringDumpRenderer,
    styles,
    type UninitializedDump,
} from '@/components/domain/Client/Response/ResponseBody/DumpRenderer/index';
import ObjectDumpValuePropertyKey from '@/components/domain/Client/Response/ResponseBody/DumpRenderer/ObjectDumpValuePropertyKey.vue';
import UninitializedDumpRenderer from '@/components/domain/Client/Response/ResponseBody/DumpRenderer/UninitializedDumpRenderer.vue';
import { DumpValueType } from '@/interfaces/generated/dump-value-types';
import { ChevronRight } from 'lucide-vue-next';
import { computed, type ComputedRef } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppDumpNodeRendererProps {
    dump: DumpValue;
    depth?: number;
    keyName?: string;
    numericalKey?: boolean;
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<AppDumpNodeRendererProps>(), {
    depth: 0,
    keyName: undefined,
    numericalKey: false,
});

/*
 * Computed & Methods.
 */

const isNestable: ComputedRef<boolean> = computed(
    () =>
        props.dump.type === DumpValueType.Object ||
        props.dump.type === DumpValueType.Closure ||
        props.dump.type === DumpValueType.Array,
);

const nestableNodeSummary: ComputedRef<string> = computed(() => {
    if (props.dump.type === DumpValueType.Closure) {
        return (props.dump as ClosureDump).value.signature;
    }

    if (props.dump.type === DumpValueType.Object) {
        const className = (props.dump as ObjectDump).value.class;
        const propCount = (props.dump as ObjectDump).value.propertiesCount;

        if (propCount === 0) {
            return className ? `${className}` : '{}';
        }

        return `${className}: ${propCount} ${propCount === 1 ? 'property' : 'properties'}`;
    }

    if (props.dump.type === DumpValueType.Array) {
        const itemCount = (props.dump as ArrayDump).value.length;

        if (itemCount === 0) {
            return '[]';
        }

        return `array: ${itemCount} ${itemCount === 1 ? 'item' : 'items'}`;
    }

    throw new Error('Node is not nestable');
});

const nestedValues: ComputedRef<
    | Record<string, DumpValue>
    | Record<string, ObjectDumpProperty>
    | Record<string, string | null>
    | never[]
> = computed(() => {
    if (props.dump.type === DumpValueType.Closure) {
        const thisValue = (props.dump as ClosureDump).value.this;
        const classValue = (props.dump as ClosureDump).value.class;

        return {
            class: {
                type: classValue ? DumpValueType.String : DumpValueType.Constant,
                value: classValue || 'null',
            },
            this: {
                type: thisValue ? DumpValueType.String : DumpValueType.Constant,
                value: thisValue || 'null',
            },
        };
    }

    if (props.dump.type === DumpValueType.Object) {
        return (props.dump as ObjectDump).value.properties;
    }

    if (props.dump.type === DumpValueType.Array) {
        return (props.dump as ArrayDump).value.items;
    }

    return [];
});
</script>

<template>
    <div v-if="!isNestable" class="flex gap-0.5">
        <slot name="key">
            <DumpKeyRenderer
                v-if="keyName !== undefined"
                :key-name="keyName"
                :numerical="numericalKey"
            />
        </slot>

        <div :class="styles.value">
            <StringDumpRenderer
                v-if="dump.type === DumpValueType.String"
                :dump="dump as StringDump"
            />

            <NumberDumpRenderer
                v-else-if="dump.type === DumpValueType.Number"
                :dump="dump as NumberDump"
            />

            <ConstDumpRenderer
                v-else-if="dump.type === DumpValueType.Constant"
                :dump="dump as ConstDump"
            />

            <UninitializedDumpRenderer
                v-else-if="dump.type === DumpValueType.Uninitialized"
                :dump="dump as UninitializedDump"
            />

            <div v-else>
                <small class="text-destructive">
                    Invalid dump value type `{{ dump.type }}` received. Please create a
                    <a
                        class="underline"
                        href="https://github.com/sunchayn/nimbus/issues/new/choose"
                    >
                        Bug
                    </a>
                    card.
                </small>
            </div>
        </div>
    </div>

    <AppCollapsible
        v-else
        class="flex flex-col"
        :default-open="depth === 0"
        data-testid="app-collapsible"
    >
        <AppCollapsibleTrigger
            class="group/collapsible-trigger -mx-1 flex items-center gap-1 rounded-sm px-1 text-sm hover:bg-zinc-100/50 dark:hover:bg-zinc-800/50"
            data-testid="collapsible-trigger"
            :disabled="nestedValues.length === 0"
        >
            <slot name="key">
                <DumpKeyRenderer
                    v-if="keyName !== undefined"
                    :key-name="keyName"
                    :numerical="numericalKey"
                />
            </slot>

            <span class="flex w-full items-center text-left">
                <ChevronRight
                    class="size-3 text-zinc-500 transition-transform group-data-[state=open]/collapsible-trigger:rotate-90"
                />
                <span class="ml-1 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ nestableNodeSummary }}
                </span>
            </span>
        </AppCollapsibleTrigger>
        <AppCollapsibleContent>
            <div class="ml-2 border-l border-zinc-200 pl-2 dark:border-zinc-800">
                <div
                    v-for="(nestedValue, key) in nestedValues"
                    :key="`${keyName ?? 'root'}-${key}`"
                    class="flex items-center gap-1 py-0.5"
                >
                    <template v-if="dump.type === DumpValueType.Object">
                        <SingleDumpRenderer
                            :dump="(nestedValue as ObjectDumpProperty).value"
                            :depth="depth + 1"
                        >
                            <template #key>
                                <ObjectDumpValuePropertyKey
                                    :key-name="key"
                                    :property="nestedValue as ObjectDumpProperty"
                                />
                            </template>
                        </SingleDumpRenderer>
                    </template>

                    <SingleDumpRenderer
                        v-else-if="
                            dump.type === DumpValueType.Array ||
                            dump.type === DumpValueType.Closure // <- Its structure is transformed in `nestedValues`
                        "
                        :dump="nestedValue as DumpValue"
                        :key-name="String(key)"
                        :numerical-key="(dump as ArrayDump).value.numericallyIndexed"
                        :depth="depth + 1"
                    />

                    <div v-else>
                        <small class="text-destructive">
                            Invalid nested value type `{{ dump.type }}` received. Please
                            create a
                            <a
                                class="underline"
                                href="https://github.com/sunchayn/nimbus/issues/new/choose"
                            >
                                Bug
                            </a>
                            card.
                        </small>
                    </div>
                </div>
            </div>
        </AppCollapsibleContent>
    </AppCollapsible>
</template>
