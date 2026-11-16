<script setup lang="ts">
/**
 * @component RequestBodyRecordPicker
 * @description Lists existing records for the fields with an exists rule, and writes the chosen one into a JSON body.
 */
import { AppButton } from '@/components/base/button';
import { AppPopover } from '@/components/base/popover';
import { RequestBodyTypeEnum } from '@/interfaces/http';
import { useLookupValuesStore } from '@/stores/request/useLookupValuesStore';
import {
    coerceToSchemaType,
    collectLookupFields,
    setJsonPayloadField,
} from '@/utils/payload/lookups';
import type { JSONSchema7 } from 'json-schema';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface RequestBodyRecordPickerProps {
    schema?: JSONSchema7;
    payloadType: RequestBodyTypeEnum;
    payload: FormData | string | null;
}

/*
 * Component Setup.
 */

const props = defineProps<RequestBodyRecordPickerProps>();

const emit = defineEmits<{
    'update:payload': [value: string | null];
}>();

const lookupValuesStore = useLookupValuesStore();

/*
 * Computed & Methods.
 */

const fields = computed(() =>
    props.payloadType === RequestBodyTypeEnum.JSON
        ? collectLookupFields(props.schema)
              .map(lookup => ({
                  ...lookup,
                  values: lookupValuesStore.valuesFor(lookup),
              }))
              .filter(lookup => lookup.values.length > 0)
        : [],
);

const choose = (field: string, event: Event) => {
    const value = (event.target as HTMLSelectElement).value;

    if (value === '') {
        return;
    }

    const candidate = fields.value.find(item => item.field === field);
    const record = candidate?.values.find(item => String(item) === value);

    if (record !== undefined && typeof props.payload !== 'object') {
        emit(
            'update:payload',
            setJsonPayloadField(
                props.payload,
                field,
                coerceToSchemaType(record, candidate?.type),
            ),
        );
    }
};
</script>

<template>
    <AppPopover
        v-if="fields.length"
        :content="{ align: 'start' }"
        :ui="{ content: 'w-64 p-0' }"
    >
        <AppButton
            size="sm"
            variant="outline"
            color="neutral"
            icon="i-hugeicons-database"
            aria-label="Existing records"
            square
            data-testid="record-picker-trigger"
        />

        <template #content>
            <p
                class="px-control-sm py-tight text-menu-label border-b-line border-default text-muted font-semibold"
                data-testid="record-picker-title"
            >
                Existing records
            </p>
            <div class="gap-base p-base flex flex-col">
                <p class="text-muted text-xs">
                    Pick a value that already exists in your database for the fields with
                    an exists rule.
                </p>
                <label
                    v-for="field in fields"
                    :key="field.field"
                    class="gap-hair flex flex-col"
                >
                    <span class="text-muted font-mono text-xs">
                        {{ field.field }} ({{ field.table }}.{{ field.column }})
                    </span>
                    <select
                        class="border-default bg-default px-tight py-hair rounded-sm border text-xs"
                        :data-testid="`record-picker-${field.field}`"
                        @change="choose(field.field, $event)"
                    >
                        <option value="">Pick a record</option>
                        <option
                            v-for="value in field.values"
                            :key="value"
                            :value="String(value)"
                        >
                            {{ value }}
                        </option>
                    </select>
                </label>
            </div>
        </template>
    </AppPopover>
</template>
