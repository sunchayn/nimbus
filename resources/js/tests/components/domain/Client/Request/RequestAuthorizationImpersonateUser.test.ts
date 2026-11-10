import RequestAuthorizationImpersonateUser from '@/components/domain/Client/Request/RequestAuthorization/RequestAuthorizationImpersonateUser.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('RequestAuthorizationImpersonateUser', () => {
    it('shows a non numeric key as written', () => {
        const wrapper = mount(RequestAuthorizationImpersonateUser, {
            props: { modelValue: '01HZX3Y8K2M4N6P8Q0R2S4T6V8' },
        });

        expect((wrapper.find('input').element as HTMLInputElement).value).toBe(
            '01HZX3Y8K2M4N6P8Q0R2S4T6V8',
        );
    });

    it('emits the typed key as a string', async () => {
        const wrapper = mount(RequestAuthorizationImpersonateUser, {
            props: { modelValue: '' },
        });

        await wrapper.find('input').setValue('abc-123');

        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['abc-123']);
    });
});
