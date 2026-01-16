import type { DumpValue } from '@/components/domain/Client/Response/ResponseBody/DumpRenderer';
import ResponseDumpAndDie from '@/components/domain/Client/Response/ResponseBody/ResponseDumpAndDie.vue';
import { DumpValueType } from '@/interfaces/generated/dump-value-types';
import { renderWithProviders, screen } from '@/tests/_utils/test-utils';
import { fireEvent } from '@testing-library/vue';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

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

describe('ResponseDumpAndDie', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.restoreAllMocks();
        vi.useRealTimers();
    });

    describe('Initialization & Parsing', () => {
        it('parses rawContent prop and creates dump snapshots', async () => {
            const snapshot = createDumpSnapshot(
                '1',
                'Test Source',
                '2024-01-01 12:00:00',
                [createStringDump('test')],
            );

            renderWithProviders(ResponseDumpAndDie, {
                props: { rawContent: JSON.stringify(snapshot) },
            });

            await nextTick();

            expect(screen.getByText('Test Source')).toBeInTheDocument();
        });

        it('handles immediate prop parsing on mount', async () => {
            const snapshot = createDumpSnapshot(
                '1',
                'Immediate Source',
                '2024-01-01 12:00:00',
                [createStringDump('test')],
            );

            renderWithProviders(ResponseDumpAndDie, {
                props: { rawContent: JSON.stringify(snapshot) },
            });

            await nextTick();

            expect(screen.getByText('Immediate Source')).toBeInTheDocument();
        });

        it('resets selected index when new dump arrives', async () => {
            const snapshot1 = createDumpSnapshot('1', 'First', '2024-01-01 12:00:00', [
                createStringDump('first'),
            ]);
            const snapshot2 = createDumpSnapshot('2', 'Second', '2024-01-01 12:01:00', [
                createStringDump('second'),
            ]);

            const { rerender } = renderWithProviders(ResponseDumpAndDie, {
                props: { rawContent: JSON.stringify(snapshot1) },
            });

            await nextTick();

            expect(screen.getByText('First')).toBeInTheDocument();

            rerender({ rawContent: JSON.stringify(snapshot2) });

            await nextTick();

            expect(screen.getByText('Second')).toBeInTheDocument();
        });

        it('handles invalid JSON gracefully', async () => {
            const consoleError = vi.spyOn(console, 'error').mockImplementation(() => { });

            renderWithProviders(ResponseDumpAndDie, {
                props: { rawContent: 'invalid json' },
            });

            await nextTick();

            expect(consoleError).toHaveBeenCalled();

            consoleError.mockRestore();
        });

        it('does not duplicate dump and selects existing one on history rewind', async () => {
            const snapshot1 = createDumpSnapshot('1', 'First', '2024-01-01 12:00:00', [
                createStringDump('first'),
            ]);
            const snapshot2 = createDumpSnapshot('2', 'Second', '2024-01-01 12:01:00', [
                createStringDump('second'),
            ]);

            const { rerender } = renderWithProviders(ResponseDumpAndDie, {
                props: { rawContent: JSON.stringify(snapshot1) },
            });

            await nextTick();

            // Receive a second dump
            rerender({ rawContent: JSON.stringify(snapshot2) });
            await nextTick();

            expect(screen.getByText(/1 \/ 2/)).toBeInTheDocument();
            expect(screen.getByText('Second')).toBeInTheDocument();

            // Simulate history rewind back to the first dump
            rerender({ rawContent: JSON.stringify(snapshot1) });
            await nextTick();

            // Should still have total 2 dumps, not 3.
            expect(screen.getByText(/2 \/ 2/)).toBeInTheDocument();
            expect(screen.getByText('First')).toBeInTheDocument();
        });
    });

    describe('Snapshot Management', () => {
        it('displays source and timestamp from snapshot', async () => {
            const snapshot = createDumpSnapshot('1', 'My Source', '2024-01-01 12:00:00', [
                createStringDump('test'),
            ]);

            renderWithProviders(ResponseDumpAndDie, {
                props: { rawContent: JSON.stringify(snapshot) },
            });

            await nextTick();

            expect(screen.getByText('My Source')).toBeInTheDocument();
            expect(screen.getByText(/Dumped At:/)).toBeInTheDocument();
            expect(
                screen.getByText('Dumped At: 2024-01-01 12:00:00'),
            ).toBeInTheDocument();
        });

        it('shows "Unknown Source" when source is missing', async () => {
            const snapshot = {
                id: '1',
                timestamp: '2024-01-01 12:00:00',
                dumps: [createStringDump('test')],
            };

            renderWithProviders(ResponseDumpAndDie, {
                props: { rawContent: JSON.stringify(snapshot) },
            });

            await nextTick();

            expect(screen.getByText('Unknown Source')).toBeInTheDocument();
        });

        it('handles empty dumps array (error state)', async () => {
            const snapshot = createDumpSnapshot(
                '1',
                'Test Source',
                '2024-01-01 12:00:00',
                [],
            );

            renderWithProviders(ResponseDumpAndDie, {
                props: { rawContent: JSON.stringify(snapshot) },
            });

            await nextTick();

            expect(screen.getByText(/Something Went Wrong!/)).toBeInTheDocument();
            expect(screen.getByText(/no dumps/)).toBeInTheDocument();
        });

        it('handles null/undefined selected dump (info state)', async () => {
            renderWithProviders(ResponseDumpAndDie, {
                props: { rawContent: JSON.stringify({ id: '1', dumps: [] }) },
            });

            await nextTick();

            expect(
                screen.getByText(
                    /Something Went Wrong! Die and Dump is detected but there is no dumps./,
                ),
            ).toBeInTheDocument();
        });
    });

    describe('Navigation', () => {
        it('previous/next buttons only visible when multiple dumps exist', async () => {
            const snapshot = createDumpSnapshot('1', 'Test', '2024-01-01 12:00:00', [
                createStringDump('test'),
            ]);

            renderWithProviders(ResponseDumpAndDie, {
                props: { rawContent: JSON.stringify(snapshot) },
            });

            await nextTick();

            const prevButton = screen.queryByTestId('previous-dump-button');
            const nextButton = screen.queryByTestId('next-dump-button');

            expect(prevButton).toBeNull();
            expect(nextButton).toBeNull();
        });

        it('navigation buttons disabled at boundaries', async () => {
            const snapshot1 = createDumpSnapshot('1', 'First', '2024-01-01 12:00:00', [
                createStringDump('first'),
            ]);

            const snapshot2 = createDumpSnapshot('2', 'Second', '2024-01-01 12:01:00', [
                createStringDump('second'),
            ]);

            const { rerender } = renderWithProviders(ResponseDumpAndDie, {
                props: { rawContent: JSON.stringify(snapshot1) },
            });

            await nextTick();

            rerender({ rawContent: JSON.stringify(snapshot2) });

            await nextTick();

            const prevButton = screen.getByTestId('previous-dump-button');
            const nextButton = screen.getByTestId('next-dump-button');

            expect(prevButton).toBeDefined();
            expect(nextButton).toBeDefined();

            expect(prevButton?.hasAttribute('disabled')).toBe(true);

            expect(nextButton?.hasAttribute('disabled')).toBe(false);
        });

        it('correct index display (1-based)', async () => {
            const snapshot1 = createDumpSnapshot('1', 'First', '2024-01-01 12:00:00', [
                createStringDump('first'),
            ]);
            const snapshot2 = createDumpSnapshot('2', 'Second', '2024-01-01 12:01:00', [
                createStringDump('second'),
            ]);

            const { rerender } = renderWithProviders(ResponseDumpAndDie, {
                props: { rawContent: JSON.stringify(snapshot1) },
            });

            await nextTick();

            rerender({ rawContent: JSON.stringify(snapshot2) });

            await nextTick();

            expect(screen.getByText(/1 \/ 2/)).toBeInTheDocument();
        });

        it('navigation updates selected dump correctly', async () => {
            const snapshot1 = createDumpSnapshot('1', 'First', '2024-01-01 12:00:00', [
                createStringDump('first'),
            ]);
            const snapshot2 = createDumpSnapshot('2', 'Second', '2024-01-01 12:01:00', [
                createStringDump('second'),
            ]);

            const { rerender } = renderWithProviders(ResponseDumpAndDie, {
                props: { rawContent: JSON.stringify(snapshot1) },
            });

            await nextTick();

            rerender({ rawContent: JSON.stringify(snapshot2) });

            await nextTick();

            expect(screen.getByText('Second')).toBeInTheDocument();

            const nextButton = screen.getByTestId('next-dump-button');

            await fireEvent.click(nextButton);

            await nextTick();

            expect(screen.getByText('First')).toBeInTheDocument();
        });
    });

    describe('Deletion Flow', () => {
        it('first click marks dump for deletion (trash icon turns red)', async () => {
            const snapshot1 = createDumpSnapshot('1', 'First', '2024-01-01 12:00:00', [
                createStringDump('first'),
            ]);
            const snapshot2 = createDumpSnapshot('2', 'Second', '2024-01-01 12:01:00', [
                createStringDump('second'),
            ]);

            const { rerender } = renderWithProviders(ResponseDumpAndDie, {
                props: { rawContent: JSON.stringify(snapshot1) },
            });

            await nextTick();

            rerender({ rawContent: JSON.stringify(snapshot2) });

            await nextTick();

            const deleteButton = screen.getByTestId('delete-dump-button');

            await fireEvent.click(deleteButton);

            await nextTick();

            const trashIcon = deleteButton.querySelector('[data-testid="trash-icon"]');
            expect(trashIcon?.classList.toString()).toContain('text-rose-500');
        });

        it('second click within timeout confirms deletion', async () => {
            const snapshot1 = createDumpSnapshot('1', 'First', '2024-01-01 12:00:00', [
                createStringDump('first'),
            ]);
            const snapshot2 = createDumpSnapshot('2', 'Second', '2024-01-01 12:01:00', [
                createStringDump('second'),
            ]);

            const { rerender, emitted } = renderWithProviders(ResponseDumpAndDie, {
                props: { rawContent: JSON.stringify(snapshot1) },
            });

            await nextTick();

            rerender({ rawContent: JSON.stringify(snapshot2) });

            await nextTick();

            const deleteButton = screen.getByTestId('delete-dump-button');

            await fireEvent.click(deleteButton);

            await nextTick();

            await fireEvent.click(deleteButton);

            await nextTick();

            expect(emitted('update:dumps')).toBeDefined();
            expect(emitted('update:dumps')[0]).toHaveLength(1);
        });

        it('deletion emits update:dumps event with updated array', async () => {
            const snapshot1 = createDumpSnapshot('1', 'First', '2024-01-01 12:00:00', [
                createStringDump('first'),
            ]);
            const snapshot2 = createDumpSnapshot('2', 'Second', '2024-01-01 12:01:00', [
                createStringDump('second'),
            ]);

            const { rerender, emitted } = renderWithProviders(ResponseDumpAndDie, {
                props: { rawContent: JSON.stringify(snapshot1) },
            });

            await nextTick();

            rerender({ rawContent: JSON.stringify(snapshot2) });

            await nextTick();

            const deleteButton = screen.getByTestId('delete-dump-button');

            await fireEvent.click(deleteButton);

            await nextTick();

            await fireEvent.click(deleteButton);

            await nextTick();

            expect(emitted('update:dumps')).toBeDefined();
            expect(emitted('update:dumps')[0]).toHaveLength(1);
        });

        it('after deletion, adjusts index correctly', async () => {
            const snapshot1 = createDumpSnapshot('1', 'First', '2024-01-01 12:00:00', [
                createStringDump('first'),
            ]);
            const snapshot2 = createDumpSnapshot('2', 'Second', '2024-01-01 12:01:00', [
                createStringDump('second'),
            ]);
            const snapshot3 = createDumpSnapshot('3', 'Third', '2024-01-01 12:02:00', [
                createStringDump('third'),
            ]);

            const { rerender } = renderWithProviders(ResponseDumpAndDie, {
                props: { rawContent: JSON.stringify(snapshot1) },
            });

            await nextTick();

            rerender({ rawContent: JSON.stringify(snapshot2) });

            await nextTick();

            rerender({ rawContent: JSON.stringify(snapshot3) });

            await nextTick();

            const nextButton = screen.getByTestId('next-dump-button');

            await fireEvent.click(nextButton);

            await nextTick();

            expect(screen.getByText('Second')).toBeInTheDocument();

            const deleteButton = screen.getByTestId('delete-dump-button');

            await fireEvent.click(deleteButton);

            await nextTick();

            await fireEvent.click(deleteButton);

            await nextTick();

            expect(screen.getByText('First')).toBeInTheDocument();
        });

        it('clears deletion state after timeout', async () => {
            const snapshot1 = createDumpSnapshot('1', 'First', '2024-01-01 12:00:00', [
                createStringDump('first'),
            ]);

            const snapshot2 = createDumpSnapshot('2', 'Second', '2024-01-01 12:01:00', [
                createStringDump('second'),
            ]);

            const { rerender } = renderWithProviders(ResponseDumpAndDie, {
                props: { rawContent: JSON.stringify(snapshot1) },
            });

            await nextTick();

            rerender({ rawContent: JSON.stringify(snapshot2) });

            await nextTick();

            const deleteButton = screen.getByTestId('delete-dump-button');

            await fireEvent.click(deleteButton);

            await nextTick();

            const trashIcon = deleteButton.querySelector('[data-testid="trash-icon"]');
            expect(trashIcon?.classList.toString()).toContain('text-rose-500');

            vi.advanceTimersByTime(1200);

            await nextTick();

            expect(trashIcon?.classList.toString()).not.toContain('text-rose-500');
        });

        it('handles deletion when no dump selected (early return)', async () => {
            renderWithProviders(ResponseDumpAndDie, {
                props: { rawContent: JSON.stringify({ id: '1', dumps: [] }) },
            });

            await nextTick();

            const deleteButton = screen.queryByTestId('delete-dump-button');

            expect(deleteButton).toBeNull();
        });
    });

    describe('Rendering', () => {
        it('renders all dumps from selected snapshot', async () => {
            const snapshot = createDumpSnapshot('1', 'Test', '2024-01-01 12:00:00', [
                createStringDump('first'),
                createStringDump('second'),
                createStringDump('third'),
            ]);

            renderWithProviders(ResponseDumpAndDie, {
                props: { rawContent: JSON.stringify(snapshot) },
            });

            await nextTick();

            const renderers = screen.getAllByTestId('single-dump-renderer');
            expect(renderers).toHaveLength(3);
        });

        it('displays dump numbers correctly (1-based)', async () => {
            const snapshot = createDumpSnapshot('1', 'Test', '2024-01-01 12:00:00', [
                createStringDump('first'),
                createStringDump('second'),
            ]);

            renderWithProviders(ResponseDumpAndDie, {
                props: { rawContent: JSON.stringify(snapshot) },
            });

            await nextTick();

            expect(screen.getByText('Dump #1')).toBeInTheDocument();
            expect(screen.getByText('Dump #2')).toBeInTheDocument();
        });

        it('passes correct dump prop to SingleDumpRenderer', async () => {
            const dump = createStringDump('test-value');
            const snapshot = createDumpSnapshot('1', 'Test', '2024-01-01 12:00:00', [
                dump,
            ]);

            renderWithProviders(ResponseDumpAndDie, {
                props: { rawContent: JSON.stringify(snapshot) },
            });

            await nextTick();

            const renderer = screen.getByTestId('single-dump-renderer');
            expect(renderer).toBeInTheDocument();
        });
    });
});
