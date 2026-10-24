import { useForwardedProps } from '@/components/base/useForwardedProps';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { defineComponent, h } from 'vue';

/*
 * Fixtures.
 */

interface DemoProps {
    fooBar?: string;
    enabled?: boolean;
    other?: string;
}

const Probe = defineComponent({
    props: {
        fooBar: { type: String, default: undefined },
        enabled: { type: Boolean, default: undefined },
        other: { type: String, default: 'fallback' },
    },
    setup(props) {
        const forwarded = useForwardedProps<DemoProps>(props);

        return () =>
            h('div', { 'data-testid': 'probe' }, JSON.stringify(forwarded.value));
    },
});

const forwardedOf = (props: Record<string, unknown>): Record<string, unknown> =>
    JSON.parse(mount(Probe, { props }).get('[data-testid="probe"]').text());

describe('useForwardedProps', () => {
    it('forwards only the props the parent passed', () => {
        expect(forwardedOf({ other: 'x' })).toEqual({ other: 'x' });
    });

    it('does not forward absent booleans or defaults', () => {
        expect(forwardedOf({})).toEqual({});
    });

    it('forwards a boolean that was passed as false', () => {
        expect(forwardedOf({ enabled: false })).toEqual({ enabled: false });
    });

    it('matches kebab-case attributes to camelCase props', () => {
        const wrapper = mount(Probe, { attrs: { 'foo-bar': 'v' } });

        expect(JSON.parse(wrapper.get('[data-testid="probe"]').text())).toEqual({
            fooBar: 'v',
        });
    });
});
