import { AppSelectMenu } from '@/components/base/select';
import CryptTool from '@/components/domain/Toolbox/CryptTool.vue';
import HashTool from '@/components/domain/Toolbox/HashTool.vue';
import SessionTool from '@/components/domain/Toolbox/SessionTool.vue';
import SignedUrlTool from '@/components/domain/Toolbox/SignedUrlTool.vue';
import TokenTool from '@/components/domain/Toolbox/TokenTool.vue';
import { flushPromises, mount, type VueWrapper } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive, type ComponentPublicInstance } from 'vue';

/*
 * Fixtures.
 */

const posts: Array<{ path: string; payload: Record<string, unknown> }> = [];

vi.mock('@/composables/toolbox/useToolRequest', async () => {
    const { shallowRef } = await import('vue');

    return {
        useToolRequest: (path: string) => {
            const result = shallowRef<unknown>(null);

            return {
                loading: shallowRef(false),
                error: shallowRef(null),
                result,
                run: async (payload: Record<string, unknown>) => {
                    posts.push({ path, payload });
                    result.value = { ok: path };
                },
            };
        },
    };
});

vi.mock('@/stores', () => ({
    useConfigStore: () => reactive({ appBasePath: '' }),
    useRoutesStore: () => ({
        routes: {
            v1: [
                {
                    name: 'root',
                    path: '',
                    routes: [
                        {
                            method: 'get',
                            endpoint: 'verify',
                            shortEndpoint: 'verify',
                            metadata: { name: 'verify' },
                        },
                        {
                            method: 'post',
                            endpoint: 'verify',
                            shortEndpoint: 'verify',
                            metadata: { name: 'verify' },
                        },
                        { method: 'get', endpoint: 'plain', shortEndpoint: 'plain' },
                    ],
                    children: [],
                },
            ],
        },
    }),
}));

const chooseRoute = async (wrapper: ReturnType<typeof mount>, name: string) => {
    (wrapper.findComponent(AppSelectMenu) as unknown as VueWrapper).vm.$emit(
        'update:modelValue',
        name,
    );
    await wrapper.vm.$nextTick();
};

const type = async (wrapper: ReturnType<typeof mount>, testId: string, value: string) => {
    await wrapper.get(`[data-testid="${testId}"]`).setValue(value);
};

describe('Toolbox tools', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        posts.length = 0;
    });

    it('decrypts and encrypts a value', async () => {
        // Arrange

        const wrapper = mount(CryptTool);

        await type(wrapper, 'crypt-value', 'secret');
        await type(wrapper, 'crypt-cookie', 'theme');

        // Act

        await wrapper.get('[data-testid="crypt-decrypt"]').trigger('click');
        await wrapper.get('[data-testid="crypt-encrypt"]').trigger('click');
        await flushPromises();

        // Assert

        expect(posts).toEqual([
            { path: 'decrypt', payload: { value: 'secret', cookie_name: 'theme' } },
            { path: 'encrypt', payload: { value: 'secret', cookie_name: 'theme' } },
        ]);
        expect(wrapper.get('[data-testid="tool-result"]').text()).toContain('encrypt');
    });

    it('verifies a signed url', async () => {
        // Arrange

        const wrapper = mount(SignedUrlTool);

        await type(wrapper, 'signed-url-url', 'http://app.test/x?signature=1');

        // Act

        await wrapper.get('[data-testid="signed-url-verify"]').trigger('click');

        // Assert

        expect(posts).toEqual([
            {
                path: 'signed-url/verify',
                payload: { url: 'http://app.test/x?signature=1' },
            },
        ]);
    });

    it('lists every named route once', () => {
        // Arrange & Act

        const wrapper = mount(SignedUrlTool);

        // Assert

        const menu = wrapper.findComponent(AppSelectMenu) as unknown as VueWrapper<
            ComponentPublicInstance<{ items: unknown }>
        >;

        expect(menu.vm.items).toEqual([{ label: 'verify', description: 'GET verify' }]);
    });

    it('creates a signed url from the route and the parameters', async () => {
        // Arrange

        const wrapper = mount(SignedUrlTool);

        await chooseRoute(wrapper, 'verify');
        await type(wrapper, 'signed-url-parameters', '{"user": 1}');
        await type(wrapper, 'signed-url-expiry', '10');

        // Act

        await wrapper.get('[data-testid="signed-url-create"]').trigger('click');

        // Assert

        expect(posts).toEqual([
            {
                path: 'signed-url/create',
                payload: {
                    route: 'verify',
                    parameters: { user: 1 },
                    expires_in_minutes: 10,
                },
            },
        ]);
    });

    it('rejects invalid parameters JSON without calling the server', async () => {
        // Arrange

        const wrapper = mount(SignedUrlTool);

        await chooseRoute(wrapper, 'verify');
        await type(wrapper, 'signed-url-parameters', '{nope');

        // Act

        await wrapper.get('[data-testid="signed-url-create"]').trigger('click');

        // Assert

        expect(posts).toEqual([]);
        expect(wrapper.get('[data-testid="tool-error"]').text()).toContain('valid JSON');
    });

    it('makes or checks a hash depending on the hash field', async () => {
        // Arrange

        const wrapper = mount(HashTool);

        await type(wrapper, 'hash-value', 'password');

        // Act

        await wrapper.get('[data-testid="hash-submit"]').trigger('click');
        await type(wrapper, 'hash-hash', '$2y$hash');
        await wrapper.get('[data-testid="hash-submit"]').trigger('click');

        // Assert

        expect(posts).toEqual([
            { path: 'hash', payload: { value: 'password', hash: null } },
            { path: 'hash', payload: { value: 'password', hash: '$2y$hash' } },
        ]);
    });

    it('decodes a jwt or finds a sanctum token', async () => {
        // Arrange

        const wrapper = mount(TokenTool);

        await type(wrapper, 'token-value', 'token');

        // Act

        await wrapper.get('[data-testid="token-jwt"]').trigger('click');
        await wrapper.get('[data-testid="token-sanctum"]').trigger('click');

        // Assert

        expect(posts.map(post => post.payload)).toEqual([
            { type: 'jwt', token: 'token' },
            { type: 'sanctum', token: 'token' },
        ]);
    });

    it('reads a session', async () => {
        // Arrange

        const wrapper = mount(SessionTool);

        await type(wrapper, 'session-value', 'abc');

        // Act

        await wrapper.get('[data-testid="session-submit"]').trigger('click');

        // Assert

        expect(posts).toEqual([{ path: 'session', payload: { value: 'abc' } }]);
    });
});
