import { useFolderDrag } from '@/composables/workspace/useFolderDrag';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { defineComponent, h } from 'vue';

describe('useFolderDrag', () => {
    it('gives the same state to a folder and the folders below it', () => {
        let parentDrag: ReturnType<typeof useFolderDrag> | undefined;
        let childDrag: ReturnType<typeof useFolderDrag> | undefined;

        const Child = () => {
            childDrag = useFolderDrag();

            return h('div');
        };

        const Parent = defineComponent({
            setup() {
                parentDrag = useFolderDrag();

                return () => h(Child);
            },
        });

        mount(Parent);

        expect(childDrag).toBe(parentDrag);
    });
});
