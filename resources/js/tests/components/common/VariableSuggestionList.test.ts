import VariableSuggestionList from '@/components/common/VariableSuggestionList.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('VariableSuggestionList', () => {
    it('renders the two groups and emits the chosen suggestion', async () => {
        const items = [
            { group: 'Variables' as const, name: 'token', token: '{{token}}' },
            { group: 'Dynamic' as const, name: 'uuid', token: '{$uuid}', sample: 'abc' },
        ];
        const wrapper = mount(VariableSuggestionList, {
            props: { items, activeIndex: 0, position: { top: 0, left: 0, width: 100 } },
            global: { stubs: { Teleport: true } },
        });

        expect(wrapper.text()).toContain('Variables');
        expect(wrapper.text()).toContain('Dynamic');
        expect(wrapper.text()).toContain('abc');

        await wrapper.findAll('[data-testid="variable-suggestion"]')[1].trigger('click');

        expect(wrapper.emitted('select')?.[0]).toEqual([items[1]]);
    });
});
