import {
    FieldContextKey,
    useFieldError,
    useIsFieldDirty,
    useIsFieldTouched,
    useIsFieldValid,
} from 'vee-validate';
import { type ComputedRef, inject, unref } from 'vue';
import { FORM_ITEM_INJECTION_KEY } from './injectionKeys';

export function useFormField(): {
    id: string | undefined;
    name: string;
    formItemId: string;
    formDescriptionId: string;
    formMessageId: string;
    valid: ComputedRef<boolean>;
    isDirty: ComputedRef<boolean>;
    isTouched: ComputedRef<boolean>;
    error: ComputedRef<string | undefined>;
} {
    const fieldContext = inject(FieldContextKey);
    const fieldItemContext = inject(FORM_ITEM_INJECTION_KEY);

    if (!fieldContext) {
        throw new Error('useFormField should be used within <FormField>');
    }

    const name = unref(fieldContext.name);
    const id = fieldItemContext;

    const fieldState = {
        valid: useIsFieldValid(name),
        isDirty: useIsFieldDirty(name),
        isTouched: useIsFieldTouched(name),
        error: useFieldError(name),
    };

    return {
        id,
        name,
        formItemId: `${id}-form-item`,
        formDescriptionId: `${id}-form-item-description`,
        formMessageId: `${id}-form-item-message`,
        ...fieldState,
    };
}
