export type PayloadPrimitive = string | number | boolean | Blob | null;

export type PayloadObject = {
    [key: string]:
        PayloadPrimitive | PayloadPrimitive[] | PayloadObject | PayloadObject[];
};

export type PayloadObjectValue =
    PayloadPrimitive | PayloadPrimitive[] | PayloadObject | PayloadObject[];
