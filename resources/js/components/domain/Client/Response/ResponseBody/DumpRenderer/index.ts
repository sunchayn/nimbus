import type { DumpValueType } from '@/interfaces/generated/dump-value-types';

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
        signature: string;
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
    key: 'text-success font-mono text-xs',
    numericalKey: 'text-info font-mono text-xs',
    objectProperty: 'text-muted-foreground font-mono text-xs',
    value: 'text-foreground font-mono text-xs',
    stringValue: 'text-success font-mono text-xs',
    meta: 'text-muted-foreground',
};

export { default as ConstDumpRenderer } from './ConstDumpRenderer.vue';
export { default as NumberDumpRenderer } from './NumberDumpRenderer.vue';
export { default as SingleDumpRenderer } from './SingleDumpRenderer.vue';
export { default as StringDumpRenderer } from './StringDumpRenderer.vue';
export { default as UninitializedDumpRenderer } from './UninitializedDumpRenderer.vue';
