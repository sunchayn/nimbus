<script setup lang="ts">
/**
 * @component RequestBodyRecordPicker
 * @description Lists existing records for the fields with an exists rule, and writes the chosen one into a JSON body.
 */
import { AppButton } from '@/components/base/button';
import { AppPopover } from '@/components/base/popover';
import { RequestBodyTypeEnum } from '@/interfaces/http';
import { useLookupValuesStore } from '@/stores';
import { collectLookupFields, setJsonPayloadField } from '@/utils/payload/lookups';
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

    const record = fields.value
        .find(candidate => candidate.field === field)
        ?.values.find(candidate => String(candidate) === value);

    if (record !== undefined && typeof props.payload !== 'object') {
        emit('update:payload', setJsonPayloadField(props.payload, field, record));
    }
};
</script>

<template>
    <AppPopover v-if="fields.length" :content="{ align: 'start' }">
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
            <div class="gap-base p-base flex w-72 flex-col text-sm">
                <div class="gap-tight flex flex-col" data-testid="record-picker-title">
                    <h4 class="text-sm leading-none font-medium">Existing records</h4>
                    <p class="text-muted text-xs">
                        Pick a value that already exists in your database for the fields
                        with an exists rule.
                    </p>
                </div>
                <label
                    v-for="field in fields"
                    :key="field.field"
                    class="gap-tight flex flex-col"
                >
                    <span class="text-xs font-medium">
                        {{ field.field }} ({{ field.table }}.{{ field.column }})
                    </span>
                    <select
                        class="border-default bg-default px-tight py-tight rounded-sm border"
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
