import type { DumpValue } from '@/components/domain/Client/Response/ResponseBody/DumpRenderer';
import ResponseDumpAndDie from '@/components/domain/Client/Response/ResponseBody/ResponseDumpAndDie.vue';
import { DumpValueType } from '@/interfaces/generated/dump-value-types';
import type { VueWrapper } from '@vue/test-utils';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

/*
 * Fixtures.
 */

vi.mock('@/components/layout/PanelSubHeader/PanelSubHeader.vue', () => ({
    default: {
        name: 'PanelSubHeader',
        template: `
            <div data-testid="panel-subheader">
                <slot />
                <div data-testid="toolbox">
                    <slot name="toolbox" />
                </div>
            </div>
        `,
    },
}));

vi.mock('@/components/base/round-indicator/AppRoundIndicator.vue', () => ({
    default: {
        name: 'AppRoundIndicator',
        template: '<div data-testid="round-indicator" />',
        props: ['class'],
    },
}));

vi.mock('@/components/domain/Client/Response/ResponseBody/DumpRenderer', () => ({
    SingleDumpRenderer: {
        name: 'SingleDumpRenderer',
        template: '<div data-testid="single-dump-renderer" />',
        props: ['dump', 'class'],
    },
}));

vi.mock('@/components/base/tooltip/AppTooltipWrapper.vue', () => ({
    default: {
        name: 'AppTooltipWrapper',
        template: '<div><slot /></div>',
    },
}));

vi.mock('lucide-vue-next', () => ({
    ChevronLeft: {
        name: 'ChevronLeft',
        template: '<svg data-testid="chevron-left" />',
    },
    ChevronRight: {
        name: 'ChevronRight',
        template: '<svg data-testid="chevron-right" />',
    },
    Trash2Icon: {
        name: 'Trash2Icon',
        template: '<svg data-testid="trash-icon" />',
    },
}));

interface DumpSnapshot {
    id: string;
    timestamp: string;
    source: string;
    dumps: DumpValue[];
}

const createDumpSnapshot = (
    id: string,
    source: string = 'Test Source',
    timestamp: string = '2024-01-01 12:00:00',
    dumps: DumpValue[] = [],
): DumpSnapshot => ({
    id,
    timestamp,
    source,
    dumps,
});

const createStringDump = (value: string): DumpValue => ({
    type: DumpValueType.String,
    value,
});

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */
const createWrapper = (options = {}): VueWrapper => {
    return mount(ResponseDumpAndDie, {
        ...options,
        global: {
            plugins: [createPinia()],
            // @ts-expect-error .global not found in object.
            ...(options.global || {}),
        },
    });
};

describe('ResponseDumpAndDie', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.useFakeTimers();
        vi.clearAllMocks();
    });

    afterEach(() => {
        vi.restoreAllMocks();
        vi.useRealTimers();
    });

    /*
     * Rendering tests.
     */

    describe('Rendering', () => {
        it('renders all dumps from selected snapshot', async () => {
            // Arrange

            const snapshot = createDumpSnapshot('1', 'Test', '2024-01-01 12:00:00', [
                createStringDump('first'),
                createStringDump('second'),
            ]);
            const wrapper = createWrapper({
                props: { rawContent: JSON.stringify(snapshot) },
            });

            // Act

            await nextTick();

            // Assert

            const renderers = wrapper.findAll('[data-testid="single-dump-renderer"]');
            expect(renderers).toHaveLength(2);
        });
    });

    /*
     * State Transition tests.
     */

    describe('Behavior', () => {
        it('navigation updates selected dump correctly', async () => {
            // Arrange

            const snapshot1 = createDumpSnapshot('1', 'First', '2024-01-01 12:00:00', [
                createStringDump('first'),
            ]);
            const snapshot2 = createDumpSnapshot('2', 'Second', '2024-01-01 12:01:00', [
                createStringDump('second'),
            ]);

            const wrapper = createWrapper({
                props: { rawContent: JSON.stringify(snapshot1) },
            });

            await nextTick();
            await wrapper.setProps({ rawContent: JSON.stringify(snapshot2) });
            await nextTick();

            // Act

            const nextButton = wrapper.get('[data-testid="next-dump-button"]');
            await nextButton.trigger('click');
            await nextTick();

            // Assert

            expect(wrapper.text()).toContain('First');
        });

        it('first click marks dump for deletion (trash icon turns red)', async () => {
            // Arrange

            const snapshot1 = createDumpSnapshot('1', 'First', '2024-01-01 12:00:00', [
                createStringDump('first'),
            ]);
            const snapshot2 = createDumpSnapshot('2', 'Second', '2024-01-01 12:01:00', [
                createStringDump('second'),
            ]);

            const wrapper = createWrapper({
                props: { rawContent: JSON.stringify(snapshot1) },
            });

            await nextTick();
            await wrapper.setProps({ rawContent: JSON.stringify(snapshot2) });
            await nextTick();

            // Act

            const deleteButton = wrapper.get('[data-testid="delete-dump-button"]');
            await deleteButton.trigger('click');
            await nextTick();

            // Assert

            const trashIcon = deleteButton.find('[data-testid="trash-icon"]');
            expect(trashIcon.classes()).toContain('text-destructive');
        });
    });
});
