export function previewDocument(source, type) {
    if (type === 'css') {
        const stylesheet = source.replace(/<\/style/gi, '<\\/style');
        return `<!doctype html><html><head><meta charset="utf-8"><style>${stylesheet}</style></head><body>
            <main class="container"><h1>Example heading</h1><p>Example paragraph</p>
            <div class="box panel card">Example panel <span class="badge">Badge</span></div>
            <nav class="nav row bar"><a href="#example">Example link</a></nav>
            <button class="btn" type="button">Example button</button></main>
        </body></html>`;
    }
    if (type !== 'js' && type !== 'javascript') return source;

    const encodedSource = JSON.stringify(source).replace(/</g, '\\u003c').replace(/\u2028/g, '\\u2028').replace(/\u2029/g, '\\u2029');
    return `<!doctype html><html><head><meta charset="utf-8"></head><body>
        <pre id="experiment-console" aria-label="Console output" role="log"></pre>
        <script>
        (function () {
            var output = document.getElementById('experiment-console');
            var lines = 0;
            function write(values) {
                if (lines++ >= 100) return;
                output.textContent += values.map(function (value) {
                    if (typeof value === 'string') return value;
                    try { return JSON.stringify(value) ?? String(value); }
                    catch { return String(value); }
                }).join(' ').slice(0, 2000) + '\\n';
            }
            ['log', 'info', 'warn', 'error'].forEach(function (method) {
                console[method] = function () { write(Array.from(arguments)); };
            });
            window.addEventListener('error', function (event) { write([event.message]); });
            window.addEventListener('unhandledrejection', function (event) { write([String(event.reason)]); });
            var script = document.createElement('script');
            script.textContent = ${encodedSource};
            document.body.appendChild(script);
        })();
        </script>
    </body></html>`;
}
