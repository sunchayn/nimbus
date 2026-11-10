<script setup lang="ts">
/**
 * @component ResponseBodyViewer
 * @description Response body with a search overlay and raw and pretty views.
 */
import { AppButton } from '@/components/base/button';
import { AppInput } from '@/components/base/input';
import { AppScrollArea } from '@/components/base/scroll-area';
import { AppSwitchButton } from '@/components/base/switch';
import CodeEditor from '@/components/domain/CodeEditor/CodeEditor.vue';
import type { RequestLog } from '@/interfaces/history/logs';
import {
    findNext,
    findPrevious,
    openSearchPanel,
    search,
    searchPanelOpen,
    SearchQuery,
    setSearchQuery,
} from '@codemirror/search';
import { EditorView } from '@codemirror/view';
import { useElementHover, useEventListener } from '@vueuse/core';
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';

/*
 * Types & Interfaces.
 */

export interface ResponseBodyViewerProps {
    log: RequestLog;
}

/*
 * Component Setup.
 */

const props = defineProps<ResponseBodyViewerProps>();

const root = useTemplateRef<HTMLElement>('root');
const editor = useTemplateRef<InstanceType<typeof CodeEditor>>('editor');
const hovered = useElementHover(root);

// The editor only highlights matches while a search panel is open. The panel stays empty, the overlay is the real UI.
const searchExtensions = [
    search({ createPanel: () => ({ dom: document.createElement('div') }) }),
];

// Counting stops here, so a very common word does not freeze the page.
const MAX_MATCHES = 10_000;

/*
 * State.
 */

const isPretty = ref(true);
const showsPretty = ref(true);
const searchOpen = ref(false);
const searchText = ref('');
const matchCount = ref(0);
const matchIndex = ref(0);

/*
 * Computed.
 */

const body = computed(() => props.log.response?.body ?? '');

const parsed = computed<{ ok: true; value: unknown } | { ok: false }>(() => {
    try {
        return { ok: true, value: JSON.parse(body.value) };
    } catch {
        return { ok: false };
    }
});

const displayed = computed(() =>
    !showsPretty.value && parsed.value.ok
        ? JSON.stringify(parsed.value.value)
        : body.value,
);

/*
 * Actions.
 */

// Rebuilding a large body blocks painting, so the switch updates first and the editor follows.
watch(isPretty, value => {
    requestAnimationFrame(() => setTimeout(() => (showsPretty.value = value)));
});

const updateMatches = () => {
    const editorView = editor.value?.view;

    if (!editorView) {
        return;
    }

    const query = new SearchQuery({ search: searchText.value });

    if (!searchPanelOpen(editorView.state)) {
        openSearchPanel(editorView);
    }

    editorView.dispatch({ effects: setSearchQuery.of(query) });

    let count = 0;
    let index = 0;

    if (query.valid) {
        const cursor = query.getCursor(editorView.state);
        const selection = editorView.state.selection.main;

        let match = cursor.next();

        while (count < MAX_MATCHES && !match.done) {
            count++;

            if (match.value.from === selection.from && match.value.to === selection.to) {
                index = count;
            }

            match = cursor.next();
        }
    }

    matchCount.value = count;
    matchIndex.value = index;
};

const step = (forward: boolean) => {
    const editorView = editor.value?.view;

    if (!editorView || matchCount.value === 0) {
        return;
    }

    (forward ? findNext : findPrevious)(editorView);

    // The editor only scrolls the match into view, so it is centered here.
    editorView.dispatch({
        effects: EditorView.scrollIntoView(editorView.state.selection.main, {
            y: 'center',
        }),
    });

    updateMatches();
};

const focusSearchInput = async () => {
    await nextTick();

    root.value
        ?.querySelector<HTMLInputElement>('input[data-testid="response-search"]')
        ?.focus();
};

const openSearch = () => {
    searchOpen.value = true;
    focusSearchInput();
};

const closeSearch = () => {
    searchOpen.value = false;
    searchText.value = '';
};

// The shortcut belongs to the viewer while the pointer is over it or the focus is inside it.
const ownsKeyboard = () =>
    hovered.value || (root.value?.contains(document.activeElement) ?? false);

const isFindShortcut = (event: KeyboardEvent) => {
    const isMac = /mac/i.test(navigator.platform);
    const modifier = isMac ? event.metaKey : event.ctrlKey;

    return (
        modifier && !event.altKey && !event.shiftKey && event.key.toLowerCase() === 'f'
    );
};

useEventListener(window, 'keydown', event => {
    if (!ownsKeyboard()) {
        return;
    }

    if (isFindShortcut(event)) {
        event.preventDefault();
        openSearch();

        return;
    }

    if (event.key === 'Escape' && searchOpen.value) {
        closeSearch();
    }
});

/*
 * Watchers.
 */

watch([searchText, displayed], () => {
    // The editor receives the new text on the next render.
    setTimeout(updateMatches);
});
</script>

<template>
    <div ref="root" class="flex min-h-0 flex-1 flex-col">
        <div
            v-if="parsed.ok"
            class="px-panel py-tight flex items-center justify-end border-b"
        >
            <AppSwitchButton
                v-model="isPretty"
                label="Pretty"
                data-testid="pretty-switch"
            />
        </div>

        <div class="relative min-h-0 flex-1">
            <AppScrollArea class="h-full">
                <CodeEditor
                    ref="editor"
                    language="json"
                    wrappable
                    wrap-key="response-body"
                    :readonly="true"
                    :placeholder="body === '' ? 'Empty' : 'Response JSON Payload'"
                    :model-value="displayed"
                    :disabled="body === ''"
                    :auto-height="false"
                    :custom-extensions="searchExtensions"
                />
            </AppScrollArea>

            <div
                v-if="searchOpen"
                class="top-tight right-tight z-sticky bg-default/40 pr-hair py-tight gap-hair hover:bg-default focus-within:bg-default absolute flex w-80 max-w-full items-center rounded-md border shadow-xs backdrop-blur-sm transition"
                role="search"
                data-testid="response-search-overlay"
            >
                <AppInput
                    v-model="searchText"
                    variant="none"
                    size="sm"
                    leading-icon="i-hugeicons-search-01"
                    placeholder="Search..."
                    aria-label="Search the response"
                    class="min-w-0 flex-1"
                    data-testid="response-search"
                    @keydown.enter.exact.prevent="step(true)"
                    @keydown.shift.enter.prevent="step(false)"
                />
                <span
                    class="text-muted shrink-0 text-xs tabular-nums"
                    data-testid="search-count"
                >
                    {{ searchText ? `${matchIndex}/${matchCount}` : '' }}
                </span>
                <AppButton
                    size="xs"
                    variant="ghost"
                    color="neutral"
                    square
                    icon="i-hugeicons-arrow-up-01"
                    aria-label="Previous match"
                    data-testid="search-previous"
                    @click="step(false)"
                />
                <AppButton
                    size="xs"
                    variant="ghost"
                    color="neutral"
                    square
                    icon="i-hugeicons-arrow-down-01"
                    aria-label="Next match"
                    data-testid="search-next"
                    @click="step(true)"
                />
                <AppButton
                    size="xs"
                    variant="ghost"
                    color="neutral"
                    square
                    icon="i-hugeicons-cancel-01"
                    aria-label="Close search"
                    data-testid="search-close"
                    @click="closeSearch"
                />
            </div>
        </div>
    </div>
</template>
