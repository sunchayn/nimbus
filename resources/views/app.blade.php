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

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet"/>

    <script>
        (function () {
            const appearance = '{{ $appearance ?? "system" }}';

            if (appearance === 'system') {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                if (prefersDark) {
                    document.documentElement.classList.add('dark');
                }
            }
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
            'routes' => isset($routes) ? json_encode($routes) : null,
            'headers' => isset($headers) ? json_encode($headers) : null,
            'routeExtractorException' => isset($routeExtractorException) ? json_encode($routeExtractorException) : null,
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
<div id="app"></div>
</body>
</html>
