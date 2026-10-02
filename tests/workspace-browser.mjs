import assert from 'node:assert/strict';
import { spawn } from 'node:child_process';
import { createServer } from 'node:http';
import { readFile, mkdtemp, rm } from 'node:fs/promises';
import { join } from 'node:path';
import { tmpdir } from 'node:os';

let input = '';
for await (const chunk of process.stdin) input += chunk;
const { html, mode } = JSON.parse(input);
const directory = await mkdtemp(join(tmpdir(), 'codequest-browser-'));
const server = createServer(async (request, response) => {
    try {
        if (request.url.startsWith('/build/')) {
            const path = request.url.split('?')[0];
            if (path.includes('..')) throw new Error('Invalid asset');
            response.setHeader('Content-Type', path.endsWith('.js') ? 'text/javascript' : path.endsWith('.css') ? 'text/css' : 'application/octet-stream');
            response.end(await readFile(join(process.cwd(), 'public', path)));
        } else {
            response.setHeader('Content-Type', 'text/html');
            response.end(html.replace(/https?:\/\/[^"'\s<>]+(?=\/build\/)/g, ''));
        }
    } catch { response.writeHead(404).end(); }
});
await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
const browser = spawn(process.env.CODEQUEST_CHROMIUM || 'chromium', [
    '--headless', '--no-sandbox', '--disable-gpu', '--disable-dev-shm-usage',
    '--remote-debugging-port=0', `--user-data-dir=${directory}`, 'about:blank',
], { stdio: ['ignore', 'ignore', 'pipe'] });
let socket;
try {
    let stderr = '';
    const endpoint = await new Promise((resolve, reject) => {
        const timer = setTimeout(() => reject(new Error(`Chromium did not start: ${stderr}`)), 10000);
        browser.once('error', (error) => { clearTimeout(timer); reject(error); });
        browser.stderr.on('data', (chunk) => {
            stderr += chunk;
            const match = stderr.match(/DevTools listening on (ws:\/\/[^\s]+)/);
            if (match) { clearTimeout(timer); resolve(match[1]); }
        });
    });
    const port = new URL(endpoint).port;
    const targets = await (await fetch(`http://127.0.0.1:${port}/json/list`)).json();
    socket = new WebSocket(targets.find((target) => target.type === 'page').webSocketDebuggerUrl);
    await new Promise((resolve, reject) => { socket.onopen = resolve; socket.onerror = reject; });
    let sequence = 0;
    const pending = new Map();
    socket.onmessage = (event) => {
        const message = JSON.parse(event.data);
        if (pending.has(message.id)) {
            const { resolve, reject, timer } = pending.get(message.id);
            clearTimeout(timer);
            pending.delete(message.id);
            if (message.error) reject(new Error(JSON.stringify(message.error)));
            else resolve(message.result);
        }
    };
    function send(method, params = {}) {
        return new Promise((resolve, reject) => {
            const id = ++sequence;
            const timer = setTimeout(() => { pending.delete(id); reject(new Error(`CDP timeout: ${method}`)); }, 10000);
            pending.set(id, { resolve, reject, timer });
            socket.send(JSON.stringify({ id, method, params }));
        });
    }
    async function evaluate(expression) {
        const result = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
        if (result.exceptionDetails) throw new Error(JSON.stringify(result.exceptionDetails));
        return result.result.value;
    }
    await send('Page.navigate', { url: `http://127.0.0.1:${server.address().port}/` });
    await evaluate(`new Promise((resolve, reject) => { const deadline = Date.now() + 8000; const timer = setInterval(() => { if (document.querySelector('.cm-content')) { clearInterval(timer); resolve(true); } else if (Date.now() > deadline) { clearInterval(timer); reject(new Error('Editor failed to initialize')); } }, 50); })`);
    if (mode === 'experiment') {
        assert.equal(await evaluate(`document.querySelector('#experiment-preview').srcdoc.includes('Try this')`), true);
        await evaluate(`window.experimentView = CodeQuest.CodeMirror.EditorView.findFromDOM(document.querySelector('.cm-editor')); experimentView.dispatch({ changes: {from: 0, to: experimentView.state.doc.length, insert: '<h1>Changed example</h1>'} }); document.querySelector('#experiment-run').click();`);
        assert.equal(await evaluate(`document.querySelector('#experiment-preview').srcdoc`), '<h1>Changed example</h1>');
        await evaluate(`document.querySelector('#experiment-reset').click()`);
        assert.equal(await evaluate(`experimentView.state.doc.toString()`), '<h1>Try this</h1>');
        assert.equal(await evaluate(`document.querySelectorAll('form[action*="/missions/"]').length`), 0);
    } else {
        await send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 900, deviceScaleFactor: 1, mobile: false });
        await evaluate(`window.originalEditor = CodeQuest.CodeMirror.EditorView.findFromDOM(document.querySelector('.cm-editor')); originalEditor.dispatch({changes: {from: 0, to: originalEditor.state.doc.length, insert: 'unsaved 日本語\\nsecond line'}}); document.querySelector('[data-workspace-resize="brief"]').dispatchEvent(new KeyboardEvent('keydown', { key: 'Home', bubbles: true }));`);
        assert.equal(await evaluate(`document.querySelector('[data-workspace-resize="brief"]').getAttribute('aria-valuenow')`), '15');
        await evaluate(`document.querySelector('[data-pane-toggle="brief"]').click()`);
        assert.equal(await evaluate(`document.querySelector('[data-workspace-pane="brief"]').getBoundingClientRect().width`), 0);
        for (const width of [390, 768, 1280]) {
            await send('Emulation.setDeviceMetricsOverride', { width, height: 900, deviceScaleFactor: 1, mobile: false });
            await evaluate(`new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)))`);
            await evaluate(`document.querySelector('[data-pane-toggle="preview"]').click(); document.querySelector('[data-pane-toggle="editor"]').click(); document.querySelector('[data-workspace-reset]').click();`);
            assert.equal(await evaluate(`CodeQuest.CodeMirror.EditorView.findFromDOM(document.querySelector('.cm-editor')) === originalEditor`), true);
            assert.equal(await evaluate(`originalEditor.state.doc.toString()`), 'unsaved 日本語\nsecond line');
            assert.equal(await evaluate(`document.documentElement.scrollWidth <= innerWidth`), true);
        }
    }
    process.stdout.write(`Chromium ${mode} checks passed\n`);
} finally {
    socket?.close();
    browser.kill('SIGTERM');
    await new Promise((resolve) => { if (browser.exitCode !== null) resolve(); else browser.once('exit', resolve); });
    await new Promise((resolve) => server.close(resolve));
    await rm(directory, { recursive: true, force: true });
}
