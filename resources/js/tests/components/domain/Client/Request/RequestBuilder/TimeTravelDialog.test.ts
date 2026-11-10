import TimeTravelDialog from '@/components/domain/Client/Request/RequestBuilder/TimeTravelDialog.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const mountDialog = (props = {}) =>
    mount(TimeTravelDialog, {
        props: {
            open: true,
            value: '',
            ...props,
        },
        global: {
            stubs: {
                AppDialog: {
                    template: '<div><slot name="body" /><slot name="footer" /></div>',
                },
                AppTabs: {
                    props: ['modelValue', 'items'],
                    emits: ['update:modelValue'],
                    template:
                        '<div><button v-for="item in items" :key="item.value" :data-mode="item.value" @click="$emit(\'update:modelValue\', item.value)">{{ item.label }}</button></div>',
                },
                AppSelect: {
                    props: ['modelValue', 'items'],
                    emits: ['update:modelValue'],
                    template:
                        '<select :value="modelValue" @change="$emit(\'update:modelValue\', $event.target.value)"><option v-for="item in items" :key="item.value" :value="item.value">{{ item.label }}</option></select>',
                },
                AppInput: {
                    props: ['modelValue'],
                    emits: ['update:modelValue'],
                    template:
                        '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
                },
                AppButton: {
                    props: ['label', 'disabled'],
                    template: '<button :disabled="disabled">{{ label }}</button>',
                },
            },
        },
    });

const apply = (wrapper: ReturnType<typeof mountDialog>) =>
    wrapper.find('[data-testid="time-travel-apply"]').trigger('click');

const input = (wrapper: ReturnType<typeof mountDialog>, id: string) =>
    wrapper.find<HTMLInputElement>(`[data-testid="${id}"]`);

describe('TimeTravelDialog', () => {
    it('starts in relative mode from a saved relative value', () => {
        const wrapper = mountDialog({ value: '-3 weeks' });

        expect(wrapper.find('[data-testid="time-travel-relative"]').exists()).toBe(true);
        expect(input(wrapper, 'time-travel-relative-input').element.value).toBe(
            '-3 weeks',
        );
    });

    it('starts in absolute mode from a saved exact time', () => {
        const wrapper = mountDialog({ value: '2030-01-31T10:00' });

        expect(wrapper.find('[data-testid="time-travel-absolute"]').exists()).toBe(true);
        expect(input(wrapper, 'time-travel-picker').element.value).toBe(
            '2030-01-31T10:00',
        );
    });

    it('warns about a saved value it cannot read', () => {
        const wrapper = mountDialog({ value: 'next friday' });

        expect(wrapper.find('[data-testid="time-travel-unsupported"]').text()).toContain(
            'next friday',
        );
    });

    it('lists the quick picks before the free form input', () => {
        const wrapper = mountDialog();
        const html = wrapper.html();

        expect(html.indexOf('time-travel-preset-0')).toBeLessThan(
            html.indexOf('time-travel-relative-input'),
        );
    });

    it.each([
        ['+1s', '+1 second'],
        ['-2d', '-2 days'],
        ['+1d 2h', '+1 day +2 hours'],
    ])('saves the free form text %j as %j', async (text, expected) => {
        const wrapper = mountDialog();

        await input(wrapper, 'time-travel-relative-input').setValue(text);
        await apply(wrapper);

        expect(wrapper.emitted('save')).toEqual([[expected]]);
        expect(wrapper.emitted('update:open')).toEqual([[false]]);
    });

    it('fills the input from a quick pick', async () => {
        const wrapper = mountDialog();

        // The sixth quick pick is Yesterday.
        await wrapper.find('[data-testid="time-travel-preset-5"]').trigger('click');

        expect(input(wrapper, 'time-travel-relative-input').element.value).toBe('-1d');

        await apply(wrapper);

        expect(wrapper.emitted('save')).toEqual([['-1 day']]);
    });

    it('updates the preview as the text changes', async () => {
        const wrapper = mountDialog();
        const before = wrapper.find('[data-testid="time-travel-preview"]').text();

        await input(wrapper, 'time-travel-relative-input').setValue('+400d');

        expect(wrapper.find('[data-testid="time-travel-preview"]').text()).not.toBe(
            before,
        );
    });

    it.each(['', '+0d', '+1', 'tomorrow', '+1d later'])(
        'blocks apply for the invalid text %j',
        async text => {
            const wrapper = mountDialog();

            await input(wrapper, 'time-travel-relative-input').setValue(text);
            await apply(wrapper);

            expect(
                wrapper.find('[data-testid="time-travel-apply"]').attributes('disabled'),
            ).toBeDefined();
            expect(wrapper.emitted('save')).toBeUndefined();
            expect(
                wrapper.find('[data-testid="time-travel-preview-value"]').classes(),
            ).toContain('text-error');
            expect(wrapper.find('[data-testid="time-travel-preview"]').text()).toContain(
                'The application clock will read',
            );
        },
    );

    it('saves an exact time in absolute mode', async () => {
        const wrapper = mountDialog();

        await wrapper.find('[data-mode="absolute"]').trigger('click');
        await input(wrapper, 'time-travel-picker').setValue('2030-01-31T10:00');
        await apply(wrapper);

        expect(wrapper.emitted('save')).toEqual([['2030-01-31T10:00']]);
    });

    it('blocks apply for an impossible date', async () => {
        const wrapper = mountDialog();

        await wrapper.find('[data-mode="absolute"]').trigger('click');
        await input(wrapper, 'time-travel-picker').setValue('2030-02-31T10:00');

        expect(
            wrapper.find('[data-testid="time-travel-apply"]').attributes('disabled'),
        ).toBeDefined();
    });

    it('uses the real time on use now', async () => {
        const wrapper = mountDialog({ value: '+1 day' });

        await wrapper.find('[data-testid="time-travel-use-now"]').trigger('click');

        expect(wrapper.emitted('save')).toEqual([['']]);
    });

    it('disables use now when no time is set', () => {
        const wrapper = mountDialog();

        expect(
            wrapper.find('[data-testid="time-travel-use-now"]').attributes('disabled'),
        ).toBeDefined();
    });
});
