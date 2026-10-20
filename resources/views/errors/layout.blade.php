<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') · @yield('title') · CodeQuest</title>
    <script>
        // Match the application theme before first paint; this page never loads built assets.
        (function () {
            var mode = 'system';
            try {
                mode = localStorage.getItem('cq-theme') || 'system';
            } catch (e) {}
            var dark = mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
            document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
        })();
    </script>
    <style>
        /* Standalone Engineer's Log fallback: no application assets, so an error still renders. */
        :root {
            color-scheme: light;
            --ground: #E9EDDF; --sheet: #FCFDF8; --ink: #1F242C; --muted: #565E67;
            --line-strong: #7D857C; --accent: #B23A2E; --accent-ink: #F4F6ED;
            --grid-minor: rgba(31, 36, 44, 0.05); --grid-major: rgba(31, 36, 44, 0.09);
        }
        .dark {
            color-scheme: dark;
            --ground: #14161B; --sheet: #1C1F26; --ink: #E6E9DE; --muted: #A9B0A5;
            --line-strong: #646D78; --accent: #E0756A; --accent-ink: #15171B;
            --grid-minor: rgba(230, 233, 222, 0.045); --grid-major: rgba(230, 233, 222, 0.075);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background-color: var(--ground);
            background-image:
                repeating-linear-gradient(0deg, var(--grid-minor) 0 1px, transparent 1px 8px),
                repeating-linear-gradient(90deg, var(--grid-minor) 0 1px, transparent 1px 8px),
                repeating-linear-gradient(0deg, var(--grid-major) 0 1px, transparent 1px 40px),
                repeating-linear-gradient(90deg, var(--grid-major) 0 1px, transparent 1px 40px);
            color: var(--ink);
            font-family: 'Barlow', 'Helvetica Neue', Arial, sans-serif;
            font-size: 16px;
            line-height: 1.6;
        }
        .page { max-width: 1120px; margin-inline: auto; padding-inline: 48px; }
        .identity { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px 24px; min-height: 76px; border-bottom: 1px solid color-mix(in srgb, var(--ink) 25%, transparent); }
        .brand { display: inline-flex; align-items: center; gap: 10px; color: var(--ink); font-family: 'Barlow Condensed', 'Barlow', sans-serif; font-size: 1.3rem; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; }
        .mark { display: block; width: 11px; height: 22px; background: var(--accent); color: transparent; overflow: hidden; }
        .system, .code { font-family: ui-monospace, 'SFMono-Regular', Consolas, monospace; font-size: 10.5px; line-height: 1.5; letter-spacing: .14em; text-transform: uppercase; }
        .system { padding: 3px 8px; border: 1px solid color-mix(in srgb, var(--ink) 45%, transparent); color: color-mix(in srgb, var(--ink) 75%, transparent); }
        main { max-width: 42rem; padding: 64px 0 56px 28px; border-left: 2px solid color-mix(in srgb, var(--accent) 80%, transparent); }
        .alert { display: block; width: 30px; height: 30px; margin-bottom: 20px; color: var(--accent); }
        .code { display: inline-block; margin: 0 0 18px; padding: 3px 8px; border: 1px solid color-mix(in srgb, var(--ink) 45%, transparent); color: color-mix(in srgb, var(--ink) 72%, transparent); }
        h1 { margin: 0; font-family: 'Barlow Condensed', 'Barlow', sans-serif; font-size: clamp(32px, 4vw, 44px); line-height: 1.05; font-weight: 600; letter-spacing: -.01em; }
        .message { margin: 16px 0 0; max-width: 60ch; color: var(--muted); overflow-wrap: anywhere; }
        .recovery { margin-top: 32px; }
        .action { display: inline-flex; align-items: center; justify-content: center; min-height: 46px; max-width: 100%; padding: 10px 18px; border: 1px solid var(--ink); border-radius: 2px; background: var(--ink); color: var(--ground); font-size: 14px; font-weight: 600; text-decoration: none; box-shadow: 3px 3px 0 color-mix(in srgb, var(--ink) 25%, transparent); transition: transform 150ms ease, box-shadow 150ms ease; }
        .action:hover { transform: translate(1px, 1px); box-shadow: 2px 2px 0 color-mix(in srgb, var(--ink) 25%, transparent); }
        a:focus-visible, [tabindex]:focus-visible { outline: 3px solid var(--accent); outline-offset: 3px; }
        .skip { position: fixed; z-index: 1; top: 8px; left: 16px; padding: 12px 16px; transform: translateY(-160%); background: var(--accent); color: var(--accent-ink); }
        .skip:focus { transform: none; }
        @media (prefers-reduced-motion: reduce) {
            .action { transition: none; }
            .action:hover { transform: none; }
        }
        @media (max-width: 899.9px) { .page { padding-inline: 24px; } main { padding-top: 40px; padding-left: 22px; } }
        @media (max-width: 479.9px) { .page { padding-inline: 16px; } .identity { min-height: 64px; } main { padding-top: 32px; padding-left: 18px; } }
        @media (max-width: 359.9px) { .mark { display: none; } }
    </style>
</head>
<body>
    <a href="#main" class="skip">Skip to error details</a>
    <div class="page">
        <header class="identity">
            <span class="brand"><span class="mark" aria-hidden="true">404</span>CodeQuest</span>
            <span class="system">System 404</span>
        </header>
        <main id="main" tabindex="-1">
            <svg class="alert" xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3" />
                <path d="M12 9v4" />
                <path d="M12 17h.01" />
            </svg>
            <p class="code">HTTP @yield('code')</p>
            <h1>@yield('title')</h1>
            <p class="message">@yield('message')</p>
            <div class="recovery">
                <a href="/" class="action">Return to start</a>
            </div>
        </main>
    </div>
</body>
</html>
