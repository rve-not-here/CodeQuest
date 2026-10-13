import assert from 'node:assert/strict';
import { spawn } from 'node:child_process';
import { createServer } from 'node:http';
import { readFile, mkdtemp, rm, mkdir, writeFile } from 'node:fs/promises';
import { join } from 'node:path';
import { tmpdir } from 'node:os';

let input = '';
for await (const chunk of process.stdin) input += chunk;
const { html, mode } = JSON.parse(input);
const directory = await mkdtemp(join(tmpdir(), 'codequest-browser-'));
let submissions = 0;
const server = createServer(async (request, response) => {
    if (request.method === 'POST') submissions++;
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
    const waitingForAttempt = ['boss-ready', 'boss-pending'].includes(mode);
    const readySelector = waitingForAttempt ? '[data-workspace-controls]' : '.cm-content';
    await evaluate(`new Promise((resolve, reject) => { const deadline = Date.now() + 8000; const timer = setInterval(() => { if (document.querySelector(${JSON.stringify(readySelector)})) { clearInterval(timer); resolve(true); } else if (Date.now() > deadline) { clearInterval(timer); reject(new Error('Workspace failed to initialize')); } }, 50); })`);
    if (!waitingForAttempt) {
        assert.equal(await evaluate(`document.querySelector('.cm-content').getAttribute('aria-label')`), 'Code editor');
    }
    if (mode === 'challenge-unavailable') {
        assert.equal(await evaluate(`document.querySelector('main').textContent.includes('Your code was not graded and no XP changed')`), true);
        assert.equal(await evaluate(`CodeQuest.CodeMirror.EditorView.findFromDOM(document.querySelector('.cm-editor')).state.doc.toString()`), '<h1>Try this</h1>');
    }
    assert.deepEqual(await evaluate(`[...document.querySelectorAll('[data-status-message]')].map(node => ({ role: node.getAttribute('role'), live: node.getAttribute('aria-live'), atomic: node.getAttribute('aria-atomic') }))`), [
        { role: null, live: null, atomic: null },
        { role: 'alert', live: null, atomic: null },
    ]);
    await evaluate(`document.querySelector('[data-status-message] [data-dismiss]').click()`);
    assert.equal(await evaluate(`document.querySelectorAll('[data-status-message]').length`), 1);
    assert.equal(await evaluate(`document.querySelector('[data-status-message]').getAttribute('role')`), 'alert');
    await evaluate(`document.querySelector('[data-status-message] [data-dismiss]').click()`);
    assert.equal(await evaluate(`document.querySelectorAll('[data-status-message]').length`), 0);
    const capture = process.env.CODEQUEST_WORKSPACE_CAPTURE;
    if (capture) {
        assert.ok(capture.startsWith(join(process.cwd(), 'storage/app/private/mission-atlas-phase5-review/')));
        await mkdir(capture, { recursive: true });
        for (const [width, height] of [[1920,1080],[1366,768],[768,1024],[390,844]]) {
            await send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: false });
            await evaluate(`new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)))`);
            const shot = await send('Page.captureScreenshot', { format: 'png' });
            await writeFile(join(capture, `${mode}-${width}.png`), Buffer.from(shot.data, 'base64'));
        }
    }
    if (waitingForAttempt) {
        assert.equal(await evaluate(`document.querySelectorAll('.cm-editor').length`), 0);
        const heading = mode === 'boss-ready' ? 'Ready to begin' : 'Verification in progress';
        assert.equal(await evaluate(`document.querySelector('.atlas-boss-state h2').textContent`), heading);
        for (const width of [390, 768, 1366, 1920]) {
            await send('Emulation.setDeviceMetricsOverride', { width, height: 768, deviceScaleFactor: 1, mobile: false });
            await evaluate(`document.querySelector('[data-pane-toggle="brief"]').click(); document.querySelector('[data-workspace-reset]').click()`);
            assert.equal(await evaluate(`document.querySelector('.atlas-boss-state form button').getBoundingClientRect().height >= 44`), true);
            assert.equal(await evaluate(`document.querySelector('.atlas-boss > nav a').getBoundingClientRect().height >= 44`), true);
            assert.equal(await evaluate(`document.documentElement.scrollWidth <= innerWidth`), true);

        }
    } else if (mode === 'experiment') {
        assert.equal(await evaluate(`document.querySelector('#experiment-preview').srcdoc.includes('Try this')`), true);
        await evaluate(`window.experimentView = CodeQuest.CodeMirror.EditorView.findFromDOM(document.querySelector('.cm-editor')); experimentView.dispatch({ changes: {from: 0, to: experimentView.state.doc.length, insert: '<h1>Changed example</h1>'} }); document.querySelector('#experiment-run').click();`);
        assert.equal(await evaluate(`document.querySelector('#experiment-preview').srcdoc`), '<h1>Changed example</h1>');
        for (const width of [390, 768, 1366, 1920]) {
            await send('Emulation.setDeviceMetricsOverride', { width, height: 844, deviceScaleFactor: 1, mobile: false });
            await evaluate(`experimentView.dispatch({selection: {anchor: 4}}); document.querySelector('[data-practice-mode="output"]').click();`);
            assert.equal(await evaluate(`document.querySelector('#practice-output').getAttribute('aria-pressed')`), 'true');
            await evaluate(`document.querySelector('[data-practice-mode="code"]').click()`);
            assert.equal(await evaluate(`CodeQuest.CodeMirror.EditorView.findFromDOM(document.querySelector('.cm-editor')) === experimentView`), true);
            assert.equal(await evaluate(`experimentView.state.doc.toString()`), '<h1>Changed example</h1>');
            assert.equal(await evaluate(`experimentView.state.selection.main.anchor`), 4);
            assert.equal(await evaluate(`document.documentElement.scrollWidth <= innerWidth`), true);
        }
        await send('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: false });
        await evaluate(`document.querySelector('[data-practice-mode="output"]').click(); document.querySelector('#experiment-reset').click()`);
        assert.deepEqual(await evaluate(`({visible: document.querySelector('#practice-code-panel').getBoundingClientRect().width > 0, focused: document.activeElement === experimentView.contentDOM, mode: document.querySelector('#practice-workspace').dataset.mode})`), {visible: true, focused: true, mode: 'code'});

        assert.equal(await evaluate(`experimentView.state.doc.toString()`), '<h1>Try this</h1>');
        assert.equal(await evaluate(`document.querySelectorAll('form[action*="/missions/"]').length`), 0);
    } else {
        await send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 900, deviceScaleFactor: 1, mobile: false });
        assert.equal(await evaluate(`document.querySelectorAll('[data-workspace-pane]').length`), 3);
        await evaluate(`window.originalEditor = CodeQuest.CodeMirror.EditorView.findFromDOM(document.querySelector('.cm-editor')); originalEditor.dispatch({changes: {from: 0, to: originalEditor.state.doc.length, insert: 'unsaved 日本語\\nsecond line'}}); document.querySelector('[data-workspace-resize="brief"]').dispatchEvent(new KeyboardEvent('keydown', { key: 'Home', bubbles: true }));`);
        assert.equal(await evaluate(`document.querySelector('[data-workspace-resize="brief"]').getAttribute('aria-valuenow')`), '15');
        await evaluate(`document.querySelector('[data-pane-toggle="brief"]').click()`);
        assert.equal(await evaluate(`document.querySelector('[data-workspace-pane="brief"]').getBoundingClientRect().width`), 0);
        await new Promise(resolve => setTimeout(resolve, 600));
        await evaluate(`originalEditor.dispatch({selection: {anchor: 3, head: 8}}); originalEditor.focus(); window.savedWorkspaceFeedback = document.querySelector('#draft-status')?.textContent;`);
        await send('Input.insertText', { text: 'changed' });
        const editedSource = await evaluate(`originalEditor.state.doc.toString()`);
        const editedSelection = await evaluate(`originalEditor.state.selection.main.anchor`);
        for (const [width, height] of [[390,844],[768,1024],[1099,768],[1101,768],[1366,768],[1920,1080],[683,384],[320,600],[1366,600]]) {
            await send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: false });
            await evaluate(`new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)))`);
            await evaluate(`document.querySelector('[data-pane-toggle="preview"]').click(); document.querySelector('[data-pane-toggle="editor"]').click(); document.querySelector('[data-workspace-reset]').click();`);
            assert.equal(await evaluate(`CodeQuest.CodeMirror.EditorView.findFromDOM(document.querySelector('.cm-editor')) === originalEditor`), true);
            assert.equal(await evaluate(`originalEditor.state.doc.toString()`), editedSource);
            assert.equal(await evaluate(`originalEditor.state.selection.main.anchor`), editedSelection);
            assert.equal(await evaluate(`document.querySelector('#draft-status')?.textContent === savedWorkspaceFeedback`), true);
            if (mode === 'boss-passed') {
                await evaluate(`document.querySelector('#run').scrollIntoView({block: 'nearest'})`);
                assert.equal(await evaluate(`document.querySelector('#run').getBoundingClientRect().bottom <= Math.min(innerHeight, document.querySelector('[data-workspace-pane="editor"]').getBoundingClientRect().bottom)`), true);
            }
            assert.equal(await evaluate(`document.documentElement.scrollWidth <= innerWidth`), true);
        }
        await evaluate(`originalEditor.focus()`);
        await send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'z', code: 'KeyZ', modifiers: 2, windowsVirtualKeyCode: 90 });
        await send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'z', code: 'KeyZ', modifiers: 2, windowsVirtualKeyCode: 90 });
        assert.equal(await evaluate(`originalEditor.state.doc.toString()`), 'unsaved 日本語\nsecond line');
        assert.deepEqual(await evaluate(`({anchor: originalEditor.state.selection.main.anchor, head: originalEditor.state.selection.main.head})`), {anchor: 3, head: 8});
        if (mode === 'boss') {
            await send('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: false });
            await evaluate(`new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)))`);
            await evaluate(`document.querySelector('#run').focus()`);
            await send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Enter', code: 'Enter', text: '\r', windowsVirtualKeyCode: 13 });
            await send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Enter', code: 'Enter', windowsVirtualKeyCode: 13 });
            await evaluate(`new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)))`);
            assert.deepEqual(await evaluate(`({focus: document.activeElement.id, outputVisible: !document.querySelector('[data-workspace-pane="preview"]').hidden})`), {focus: 'workspace-tab-preview', outputVisible: true});
            assert.equal(await evaluate(`document.querySelector('[data-workspace-pane="preview"]').hidden`), false);
            await evaluate(`document.querySelector('[data-pane-toggle="editor"]').click()`);
            await send('Page.enable');
            const dialog = new Promise((resolve) => {
                function opened(event) {
                    const message = JSON.parse(event.data);
                    if (message.method !== 'Page.javascriptDialogOpening') return;
                    socket.removeEventListener('message', opened);
                    resolve(message.params);
                }
                socket.addEventListener('message', opened);
            });
            await evaluate(`setTimeout(() => document.querySelector('#assessment-submit button').click(), 0)`);
            const confirmation = await dialog;
            assert.equal(confirmation.type, 'confirm');
            assert.ok(confirmation.message.includes('records the result of your current attempt'));
            await send('Page.handleJavaScriptDialog', { accept: false });
            await evaluate(`new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)))`);
            assert.equal(submissions, 0);
            assert.equal(await evaluate(`CodeQuest.CodeMirror.EditorView.findFromDOM(document.querySelector('.cm-editor')) === originalEditor`), true);
        }
    }
    process.stdout.write(`Chromium ${mode} checks passed\n`);
} finally {
    socket?.close();
    browser.kill('SIGTERM');
    await new Promise((resolve) => { if (browser.exitCode !== null) resolve(); else browser.once('exit', resolve); });
    await new Promise((resolve) => server.close(resolve));
    await rm(directory, { recursive: true, force: true, maxRetries: 3, retryDelay: 100 });
}
