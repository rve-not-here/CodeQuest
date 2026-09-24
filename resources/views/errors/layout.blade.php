<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') · @yield('title') · CodeQuest</title>
    <style>
        :root { color-scheme: dark; font-family: ui-monospace, 'Cascadia Code', Consolas, monospace; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 1.5rem; background: #060c08; color: #e1ece4; }
        main { width: min(100%, 38rem); padding: clamp(1.5rem, 5vw, 2.5rem); border: 1px solid #559260; background: #0b1610; }
        .brand { margin: 0 0 2rem; color: #33ff00; font-size: .75rem; font-weight: 700; letter-spacing: .08em; }
        .code { margin: 0 0 .5rem; color: #ff5c5c; font-size: .75rem; font-weight: 700; }
        h1 { margin: 0; font-size: 1.5rem; line-height: 1.2; }
        p { margin: 1rem 0 0; max-width: 60ch; line-height: 1.6; color: #91a198; }
        a { display: inline-block; margin-top: 2rem; padding: .7rem 1rem; border: 1px solid #33ff00; color: #33ff00; text-decoration: none; font-weight: 700; }
        a:hover { background: #33ff00; color: #030604; }
        a:focus-visible { outline: 2px solid #49d8e8; outline-offset: 3px; }
    </style>
</head>
<body>
    <main>
        <p class="brand">CODEQUEST // SYSTEM 404</p>
        <p class="code">ERROR @yield('code')</p>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <a href="/">Return to start</a>
    </main>
</body>
</html>
