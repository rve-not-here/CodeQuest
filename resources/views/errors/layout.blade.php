<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') · @yield('title') · CodeQuest</title>
    <style>
        /* Standalone fallback mirrors Mission Atlas without requiring application assets. */
        :root { color-scheme: dark; --canvas: #111416; --fg: #F2F3EE; --muted: #AAB4B8; --line: #343E43; --accent: #C4F56A; --accent-hover: #D4FA92; font-family: 'IBM Plex Sans', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif; }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--canvas); color: var(--fg); font-size: 16px; line-height: 1.625; }
        .page { max-width: 1120px; margin-inline: auto; padding-inline: 48px; }
        .identity { display: flex; align-items: center; justify-content: space-between; gap: 24px; min-height: 72px; border-bottom: 1px solid var(--line); }
        .brand { display: inline-flex; align-items: center; gap: 12px; font-size: 18px; font-weight: 600; }
        .mark { display: grid; place-items: center; width: 32px; height: 32px; border-block: 1px solid var(--accent); color: var(--accent); }
        .mark, .system, .code { font-family: 'IBM Plex Mono', ui-monospace, 'SFMono-Regular', Consolas, monospace; font-size: 12px; line-height: 1.5; letter-spacing: .04em; }
        .system, .code { color: var(--muted); }
        main { max-width: 40rem; padding-block: 64px 48px; scroll-margin-top: 24px; }
        .code { margin: 0 0 16px; }
        h1 { margin: 0; font-size: 32px; font-weight: 600; line-height: 1.25; letter-spacing: -.03em; }
        .message { margin: 16px 0 0; max-width: 60ch; color: var(--muted); overflow-wrap: anywhere; }
        .recovery { margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--line); }
        .action { display: inline-flex; align-items: center; justify-content: center; min-height: 48px; max-width: 100%; padding: 12px 16px; border: 1px solid var(--accent); border-radius: 2px; background: var(--accent); color: var(--canvas); font-size: 14px; line-height: 1.5; font-weight: 600; text-decoration: none; }
        .action:hover { background: var(--accent-hover); border-color: var(--accent-hover); }
        a:focus-visible, [tabindex]:focus-visible { outline: 3px solid var(--accent); outline-offset: 3px; }
        .skip { position: fixed; z-index: 1; top: 8px; left: 16px; padding: 12px 16px; transform: translateY(-160%); background: var(--accent); color: var(--canvas); }
        .skip:focus { transform: none; }
        @media (max-width: 899.9px) { .page { padding-inline: 24px; } main { padding-top: 40px; } h1 { font-size: 28px; } }
        @media (max-width: 479.9px) { .page { padding-inline: 16px; } .identity { min-height: 64px; gap: 12px; } main { padding-top: 32px; } }
        @media (max-width: 359.9px) { .mark { display: none; } }
    </style>
</head>
<body>
    <a href="#main" class="skip">Skip to error details</a>
    <div class="page">
        <header class="identity">
            <span class="brand"><span class="mark" aria-hidden="true">404</span>CodeQuest</span>
            <span class="system">SYSTEM 404</span>
        </header>
        <main id="main" tabindex="-1">
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
