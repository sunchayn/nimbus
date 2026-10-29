<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="icon" type="image/png" href="{{ asset('/vendor/nimbus/favicon/favicon-96x96.png') }}" sizes="96x96"/>
    <link rel="icon" type="image/svg+xml" href="{{ asset('/vendor/nimbus/favicon/favicon.svg') }}"/>
    <link rel="shortcut icon" href="{{ asset('/vendor/nimbus/favicon/favicon.ico') }}"/>
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('/vendor/nimbus/favicon/apple-touch-icon.png') }}"/>
    <meta name="apple-mobile-web-app-title" content="Nimbus"/>

    <title>{{ config('app.name', 'Laravel') }} - Nimbus</title>

    <script>
        (function () {
            let theme = 'system';

            try {
                const stored = JSON.parse(localStorage.getItem('nimbus-user-preferences'))?.theme;

                if (['light', 'dark', 'system'].includes(stored)) {
                    theme = stored;
                }
            } catch (e) {}

            const dark = theme === 'system'
                ? window.matchMedia('(prefers-color-scheme: dark)').matches
                : theme === 'dark';

            document.documentElement.classList.toggle('dark', dark);

            // The loading shell reads the saved explorer layout, so a collapsed sidebar stays collapsed.
            try {
                const open = localStorage.getItem('nimbus:explorer-open') !== 'false';
                const savedSize = key => {
                    const saved = Object.values(JSON.parse(localStorage.getItem(key)) ?? {})[0];
                    const value = saved?.layout?.[0];

                    return typeof value === 'number' && value > 0 && value < 100 ? value : null;
                };
                const size = savedSize('reka:main-splitter-group');
                const requestSize = savedSize('reka:client-splitter-group');
                const style = document.documentElement.style;

                if (!open) {
                    style.setProperty('--shell-explorer-width', '0px');
                    style.setProperty('--shell-handle-width', '0px');
                } else if (size !== null) {
                    style.setProperty('--shell-explorer-width', size + '%');
                }

                if (requestSize !== null) {
                    style.setProperty('--shell-request-size', requestSize);
                    style.setProperty('--shell-response-size', 100 - requestSize);
                }
            } catch (e) {}
        })();
    </script>
    @php
        /** @var \Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver $activeApplicationResolver */
        $appBasePath = rtrim(
            \Illuminate\Support\Str::start(
                parse_url(config('app.url'), PHP_URL_PATH) ?? '',
                prefix: '/'
            ),
            characters: '/',
        );

        $config = \Illuminate\Support\Js::from([
            'basePath' => $appBasePath . '/' . ltrim(config('nimbus.prefix'), '/'),
            'headers' => isset($headers) ? json_encode($headers) : null,
            'globalException' => isset($globalException) ? json_encode($globalException) : null,
            'isVersioned' => $activeApplicationResolver->isVersioned(),
            'apiBaseUrl' => $activeApplicationResolver->getApiBaseUrl(),
            'currentUser' => isset($currentUser) ? json_encode($currentUser) : null,
            'applications' => $activeApplicationResolver->getAvailableApplications(),
            'activeApplication' => $activeApplicationResolver->getActiveApplicationKey(),
            'sharedState' => isset($sharedState) ? $sharedState : null,
            'primaryProcessorName' => isset($primaryProcessorName) ? $primaryProcessorName : null,
            'showOperationId' => isset($showOperationId) ? $showOperationId : false,
        ]);

        $configTag = new \Illuminate\Support\HtmlString(<<<HTML
            <script type="module">
                window.Nimbus = {$config};
            </script>
        HTML);
    @endphp

    {{ $configTag }}

    @vite(['resources/css/app.css', 'resources/js/app/app.ts'])
</head>
<body class="font-sans antialiased">
<div id="app" class="isolate">
    @include('nimbus::partials.loading-shell')
</div>
<noscript>
    <style>#nimbus-loading-shell { display: none; }</style>
    <p style="padding: 1rem; font-family: sans-serif;">Nimbus needs JavaScript to run. Enable it and reload the page.</p>
</noscript>
<script>
    // The shell is replaced when the app mounts. If it is still here, the app did not load.
    setTimeout(function () {
        const shell = document.getElementById('nimbus-loading-shell');

        if (shell) {
            shell.setAttribute('aria-busy', 'false');
            shell.querySelector('[data-shell-timeout]').hidden = false;
        }
    }, 15000);
</script>
</body>
</html>
