import { camelize, computed, getCurrentInstance, type ComputedRef } from 'vue';

/**
 * Returns only the props the parent really passed.
 * A wrapper that binds every declared prop would send `false` for absent booleans and turn uncontrolled Nuxt UI components into controlled ones.
 */
export function useForwardedProps<T extends object>(props: T): ComputedRef<Partial<T>> {
    const instance = getCurrentInstance();

    return computed(() => {
        const passed = new Set(Object.keys(instance?.vnode.props ?? {}).map(camelize));

        return Object.fromEntries(
            Object.entries(props).filter(([key]) => passed.has(key)),
        ) as Partial<T>;
    });
}
