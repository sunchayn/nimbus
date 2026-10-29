{{--
    Static copy of the app layout, shown until the JavaScript app mounts and replaces it.
    The Vue skeletons in resources/js/components/layout/skeleton draw the same shapes, so keep both in sync.
--}}
@php
    $isSecondaryPage = in_array(request()->route('view'), ['settings', 'environments'], true);
    $block = 'animate-pulse rounded-md bg-accented/50 motion-reduce:animate-none';
@endphp

<div
    id="nimbus-loading-shell"
    role="status"
    aria-busy="true"
    aria-label="Loading Nimbus"
    class="bg-muted relative flex h-svh w-full flex-col"
>
    <header
        data-testid="skeleton-top-bar"
        class="h-toolbar px-base bg-default gap-base grid grid-cols-[1fr_auto_1fr] items-center border-b"
    >
        <div class="gap-base flex min-w-0 items-center">
            <div class="gap-base flex items-center">
                <img src="{{ asset('/vendor/nimbus/favicon/favicon.svg') }}" alt="" class="size-icon-lg" />
                <span class="text-highlighted text-sm font-semibold max-sm:hidden">Nimbus</span>
            </div>
            <div class="border-default h-chip-sm border-l"></div>
            <div class="{{ $block }} h-control-sm w-control-sm"></div>
        </div>

        <div class="{{ $block }} h-control-sm w-32"></div>

        <div class="gap-base flex items-center justify-end">
            <div class="max-xs:hidden gap-tight flex items-center">
                <div class="{{ $block }} h-control-sm w-control-sm"></div>
                <div class="{{ $block }} h-control-sm w-control-sm"></div>
            </div>
            <div class="border-default max-xs:hidden h-chip-sm border-l"></div>
            <div class="{{ $block }} h-control-sm w-control-sm"></div>
        </div>
    </header>

    <main class="min-h-0 min-w-0 flex-1">
        @if ($isSecondaryPage)
            <div class="p-base h-full" data-testid="skeleton-page">
                <div class="bg-default border-default rounded-pane flex h-full flex-col overflow-hidden border">
                    <div class="h-toolbar px-panel flex items-center border-b">
                        <div class="{{ $block }} h-control-xs w-20"></div>
                    </div>
                    <div class="h-sub-toolbar bg-muted border-b"></div>
                    <div class="p-panel gap-base flex flex-col">
                        <div class="{{ $block }} h-control-md w-1/3"></div>
                        <div class="{{ $block }} h-control-md w-2/3"></div>
                        <div class="{{ $block }} h-control-md w-1/2"></div>
                        <div class="{{ $block }} h-control-md w-3/5"></div>
                    </div>
                </div>
            </div>
        @else
            <div class="p-base flex h-full overflow-hidden">
                {{-- The inline script in the head sets the sidebar variables from the saved layout. --}}
                <aside
                    data-testid="skeleton-sidebar"
                    class="gap-tight flex shrink-0 flex-col overflow-hidden max-md:hidden"
                    style="width: var(--shell-explorer-width, 20%)"
                >
                    <div class="{{ $block }} h-control-md w-full"></div>
                    <div class="gap-tight flex flex-col">
                        <div class="min-h-8"></div>
                        <div class="{{ $block }} h-control-sm w-2/3"></div>
                        <div class="{{ $block }} h-control-sm ms-base w-1/2"></div>
                        <div class="{{ $block }} h-control-sm ms-base w-3/5"></div>
                        <div class="{{ $block }} h-control-sm w-1/2"></div>
                        <div class="{{ $block }} h-control-sm w-3/4"></div>
                        <div class="{{ $block }} h-control-sm ms-base w-2/5"></div>
                        <div class="{{ $block }} h-control-sm w-3/5"></div>
                    </div>
                </aside>
                <div
                    class="shrink-0 max-md:hidden"
                    style="width: var(--shell-handle-width, var(--spacing-group))"
                ></div>

                <section class="gap-group @container flex h-full min-w-0 flex-1 flex-col" data-testid="skeleton-workspace">
                    <div class="gap-pane flex shrink-0 items-center">
                        <div class="{{ $block }} h-control-md w-control-md"></div>
                        <div class="{{ $block }} h-control-md w-20"></div>
                    </div>

                    {{-- The row layout starts at a wide container, close to the breakpoint in ClientWorkspace. --}}
                    <div class="gap-pane flex min-h-0 flex-1 flex-col @4xl:flex-row">
                        <div class="gap-pane flex min-h-0 min-w-0 flex-col" style="flex: var(--shell-request-size, 1) 1 0%" data-testid="skeleton-request">
                            <div class="pane-surface p-tight shrink-0">
                                <div class="{{ $block }} h-control-md w-full"></div>
                            </div>
                            <div class="pane-surface flex min-h-0 flex-1 flex-col">
                                <div class="h-toolbar px-panel gap-base flex shrink-0 items-center border-b">
                                    <div class="{{ $block }} h-control-xs w-16"></div>
                                    <div class="{{ $block }} h-control-xs w-16"></div>
                                    <div class="{{ $block }} h-control-xs w-16"></div>
                                </div>
                                <div class="p-panel flex-1">
                                    <div class="{{ $block }} h-full w-full"></div>
                                </div>
                            </div>
                        </div>

                        <div class="gap-pane flex min-h-0 min-w-0 flex-col" style="flex: var(--shell-response-size, 1) 1 0%" data-testid="skeleton-response">
                            <div class="pane-surface h-pane-header px-panel flex shrink-0 items-center">
                                <div class="{{ $block }} h-control-xs w-20"></div>
                            </div>
                            <div class="pane-surface p-panel flex min-h-0 flex-1 flex-col">
                                <div class="{{ $block }} h-full w-full"></div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        @endif
    </main>

    <p
        data-shell-timeout
        role="alert"
        hidden
        class="bg-default border-default text-default px-base py-tight absolute bottom-base left-1/2 -translate-x-1/2 rounded-md border text-sm"
    >
        Nimbus is taking too long to load. Check your connection and reload the page.
    </p>
</div>
