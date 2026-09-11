<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="icon" href="/favicon.ico">
        <link rel="manifest" href="/site.webmanifest">
        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <script>
            try {
                var t = localStorage.getItem('app-theme');
                if (t !== 'light' && t !== 'dark') {
                    t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                }
                if (t === 'dark') document.documentElement.classList.add('dark');
            } catch (e) {}
        </script>
        <style>
            #boot-splash {
                position: fixed;
                inset: 0;
                z-index: 100;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #f6f8fb;
                transition: opacity 0.35s ease;
            }
            html.dark #boot-splash {
                background: #0f172a;
            }
            #boot-splash.boot-splash-hide {
                opacity: 0;
                pointer-events: none;
            }
            .boot-splash-inner {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 1.25rem;
            }
            .boot-splash-logo {
                position: relative;
                overflow: hidden;
                border-radius: 1rem;
                animation: boot-pulse 1.8s ease-in-out infinite;
            }
            .boot-splash-logo img {
                display: block;
                width: 5rem;
                height: 5rem;
                object-fit: contain;
                border-radius: 1rem;
            }
            .boot-splash-shine {
                position: absolute;
                inset: 0;
                background: linear-gradient(105deg, transparent 30%, rgba(255, 255, 255, 0.55) 50%, transparent 70%);
                transform: translateX(-120%);
                animation: boot-shimmer 1.8s ease-in-out infinite;
            }
            html.dark .boot-splash-shine {
                background: linear-gradient(105deg, transparent 30%, rgba(255, 255, 255, 0.22) 50%, transparent 70%);
            }
            .boot-splash-name {
                font-family: "Figtree", ui-sans-serif, system-ui, sans-serif;
                font-size: 0.875rem;
                font-weight: 500;
                letter-spacing: 0.18em;
                text-transform: uppercase;
                color: #0f172a;
            }
            html.dark .boot-splash-name {
                color: #f1f5f9;
            }
            .boot-splash-bar {
                width: 12rem;
                height: 0.25rem;
                border-radius: 9999px;
                overflow: hidden;
                background: rgba(100, 116, 139, 0.25);
            }
            .boot-splash-bar span {
                display: block;
                height: 100%;
                width: 50%;
                border-radius: 9999px;
                background: var(--brand-primary, #1d4ed8);
                animation: boot-bar 1.2s ease-in-out infinite;
            }
            .boot-splash-hint {
                font-family: "Figtree", ui-sans-serif, system-ui, sans-serif;
                font-size: 0.75rem;
                font-weight: 400;
                color: #64748b;
            }
            html.dark .boot-splash-hint {
                color: #94a3b8;
            }
            @keyframes boot-pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.05); }
            }
            @keyframes boot-shimmer {
                0% { transform: translateX(-120%); }
                60%, 100% { transform: translateX(120%); }
            }
            @keyframes boot-bar {
                0% { transform: translateX(-100%); }
                100% { transform: translateX(300%); }
            }
        </style>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        <div id="boot-splash">
            <div class="boot-splash-inner">
                <div class="boot-splash-logo">
                    <img src="/logo-carto.png" alt="Cargando" />
                    <span class="boot-splash-shine"></span>
                </div>
                <div class="boot-splash-name">{{ config('app.name', 'Carto') }}</div>
                <div class="boot-splash-bar"><span></span></div>
                <div class="boot-splash-hint">Cargando página…</div>
            </div>
        </div>
        @inertia
    </body>
</html>
