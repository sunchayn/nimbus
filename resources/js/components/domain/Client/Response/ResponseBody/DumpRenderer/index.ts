import { DumpValueType } from '@/interfaces/generated/dump-value-types';

export interface ArrayDump {
    type: DumpValueType.Array;
    value: {
        items: Record<string, DumpValue>;
        length: number;
        numericallyIndexed: boolean;
    };
}

export interface ObjectDump {
    type: DumpValueType.Object;
    value: {
        class: string;
        properties: Record<string, ObjectDumpProperty>;
        propertiesCount: number;
    };
}

export interface ClosureDump {
    type: DumpValueType.Closure;
    value: {
        signature: string | null;
        class: string | null;
        this: string | null;
    };
}

export interface StringDump {
    type: DumpValueType.String;
    value: string;
}

export interface NumberDump {
    type: DumpValueType.Number;
    value: number;
}

export interface ConstDump {
    type: DumpValueType.Constant;
    value: boolean | null;
}

export interface UninitializedDump {
    type: DumpValueType.Uninitialized;
    value: string;
}

export type DumpValue =
    | ArrayDump
    | ObjectDump
    | ClosureDump
    | StringDump
    | NumberDump
    | ConstDump
    | UninitializedDump
    | {
          type: string;
      };

export interface ObjectDumpProperty {
    visibility: 'public' | 'protected' | 'private';
    value: DumpValue;
}

export const styles = {
    key: 'text-green-600 dark:text-green-400 font-mono text-xs',
    numericalKey: 'text-blue-600 font-mono text-xs',
    objectProperty: 'text-zinc-600 dark:text-zinc-400 font-mono text-xs',
    value: 'text-xs text-zinc-900 dark:text-zinc-50 font-mono',
    stringValue: 'text-xs text-green-600 dark:text-green-400 font-mono',
    meta: 'text-zinc-600 dark:text-zinc-400',
};

export { default as ConstDumpRenderer } from './ConstDumpRenderer.vue';
export { default as NumberDumpRenderer } from './NumberDumpRenderer.vue';
export { default as SingleDumpRenderer } from './SingleDumpRenderer.vue';
export { default as StringDumpRenderer } from './StringDumpRenderer.vue';
export { default as UninitializedDumpRenderer } from './UninitializedDumpRenderer.vue';
