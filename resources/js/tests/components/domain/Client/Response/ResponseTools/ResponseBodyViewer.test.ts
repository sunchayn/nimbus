import ResponseBodyViewer from '@/components/domain/Client/Response/ResponseTools/ResponseBodyViewer.vue';
import type { RequestLog } from '@/interfaces/history/logs';
import { EditorView } from '@codemirror/view';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';
import {
    createMockRequestLog,
    createMockResponse,
} from '../../../../../_utils/testFactories';

const logWith = (body: string, overrides = {}, timestamp = 100): RequestLog =>
    createMockRequestLog({
        response: createMockResponse({ body, timestamp, ...overrides }),
    });

const mountViewer = (log: RequestLog) =>
    mount(ResponseBodyViewer, {
        props: { log },
        global: { stubs: { CodeEditor: true, AppScrollArea: false } },
    });

describe('ResponseBodyViewer', () => {
    afterEach(() => {
        vi.useRealTimers();
    });

    it('shows the raw body when the pretty switch is off', async () => {
        vi.useFakeTimers();

        const body = '{\n   "a": 1\n}';
        const wrapper = mountViewer(logWith(body));
        const editor = () => wrapper.findComponent({ name: 'CodeEditor' });
        const toggle = wrapper.findComponent({ name: 'AppSwitch' });

        expect(editor().props('modelValue')).toBe(body);

        toggle.vm.$emit('update:modelValue', false);
        await vi.advanceTimersByTimeAsync(50);

        expect(editor().props('modelValue')).toBe('{"a":1}');

        toggle.vm.$emit('update:modelValue', true);
        await vi.advanceTimersByTimeAsync(50);

        expect(editor().props('modelValue')).toBe(body);
    });

    it('shows the pretty switch only for a JSON body', () => {
        const json = mountViewer(logWith('{}'));
        const html = mountViewer(logWith('<p>Hi</p>'));

        expect(json.find('[data-testid="pretty-switch"]').exists()).toBe(true);
        expect(html.find('[data-testid="pretty-switch"]').exists()).toBe(false);
    });

    it('has no preview for HTML responses', () => {
        const wrapper = mountViewer(
            logWith('<p>Hi</p>', {
                headers: [{ key: 'Content-Type', value: 'text/html' }],
            }),
        );

        expect(wrapper.find('[data-testid="response-preview"]').exists()).toBe(false);
    });

    describe('search', () => {
        const pressFind = (modifiers: KeyboardEventInit = { ctrlKey: true }) =>
            window.dispatchEvent(
                new KeyboardEvent('keydown', {
                    key: 'f',
                    bubbles: true,
                    ...modifiers,
                }),
            );

        const mountAttached = async (body: string) => {
            // The DOM test environment cannot measure text, which the editor needs.
            const emptyRects = Object.assign([], { item: () => null });
            Range.prototype.getClientRects = () => emptyRects as unknown as DOMRectList;
            Range.prototype.getBoundingClientRect = () => new DOMRect();

            const wrapper = mount(ResponseBodyViewer, {
                props: { log: logWith(body) },
                attachTo: document.body,
            });

            // The hover tracking starts after the first render.
            await flushPromises();

            return wrapper;
        };

        it('is hidden until the find shortcut is pressed over the viewer', async () => {
            const wrapper = await mountAttached('{"a":"foo"}');

            expect(wrapper.find('[data-testid="response-search-overlay"]').exists()).toBe(
                false,
            );

            pressFind();
            await wrapper.vm.$nextTick();

            expect(wrapper.find('[data-testid="response-search-overlay"]').exists()).toBe(
                false,
            );

            await wrapper.trigger('mouseenter');
            pressFind();
            await wrapper.vm.$nextTick();

            expect(wrapper.find('[data-testid="response-search-overlay"]').exists()).toBe(
                true,
            );

            wrapper.unmount();
        });

        it('opens with the find shortcut when the focus is inside the viewer', async () => {
            const wrapper = await mountAttached('{"a":"foo"}');

            wrapper.element.setAttribute('tabindex', '0');
            (wrapper.element as HTMLElement).focus();
            pressFind();
            await wrapper.vm.$nextTick();

            expect(wrapper.find('[data-testid="response-search-overlay"]').exists()).toBe(
                true,
            );

            wrapper.unmount();
        });

        it('closes with Escape and clears the search', async () => {
            const wrapper = await mountAttached('{"a":"foo"}');

            await wrapper.trigger('mouseenter');
            pressFind();
            await flushPromises();
            await wrapper.find('input[data-testid="response-search"]').setValue('foo');

            window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
            await wrapper.vm.$nextTick();

            expect(wrapper.find('[data-testid="response-search-overlay"]').exists()).toBe(
                false,
            );

            pressFind();
            await wrapper.vm.$nextTick();

            expect(
                (
                    wrapper.find('input[data-testid="response-search"]')
                        .element as HTMLInputElement
                ).value,
            ).toBe('');

            wrapper.unmount();
        });

        it('counts search matches and steps through them', async () => {
            vi.useFakeTimers();

            const wrapper = await mountAttached('{"a":"foo","b":"foo","c":"bar"}');

            await wrapper.trigger('mouseenter');
            pressFind();
            await vi.runAllTimersAsync();
            await wrapper.find('input[data-testid="response-search"]').setValue('foo');
            await vi.runAllTimersAsync();

            expect(wrapper.find('[data-testid="search-count"]').text()).toBe('0/2');

            await wrapper.find('[data-testid="search-next"]').trigger('click');
            await vi.runAllTimersAsync();

            expect(wrapper.find('[data-testid="search-count"]').text()).toBe('1/2');

            const { view } = wrapper.findComponent({ name: 'CodeEditor' })
                .vm as unknown as {
                view: EditorView;
            };

            // The second match starts at offset 16 and must be centered.
            const scroll = vi.spyOn(EditorView, 'scrollIntoView');

            await wrapper.find('[data-testid="search-next"]').trigger('click');

            expect(view.state.selection.main.from).toBe(16);
            expect(scroll).toHaveBeenLastCalledWith(
                expect.objectContaining({ from: 16 }),
                { y: 'center' },
            );

            wrapper.unmount();
        });

        it('steps forward with Enter and backward with Shift+Enter', async () => {
            vi.useFakeTimers();

            const wrapper = await mountAttached('{"a":"foo","b":"foo","c":"bar"}');

            await wrapper.trigger('mouseenter');
            pressFind();
            await vi.runAllTimersAsync();

            const input = wrapper.find('input[data-testid="response-search"]');

            await input.setValue('foo');
            await vi.runAllTimersAsync();

            await input.trigger('keydown', { key: 'Enter' });
            await input.trigger('keydown', { key: 'Enter' });

            expect(wrapper.find('[data-testid="search-count"]').text()).toBe('2/2');

            await input.trigger('keydown', { key: 'Enter', shiftKey: true });

            expect(wrapper.find('[data-testid="search-count"]').text()).toBe('1/2');

            wrapper.unmount();
        });
    });
});
