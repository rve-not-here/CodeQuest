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
    const capture = process.env.CODEQUEST_WORKSPACE_CAPTURE;
    const readOnlyModes = ['dashboard', 'learning-path'];
    const isReadOnly = readOnlyModes.includes(mode);
    const readySelector = waitingForAttempt ? '[data-workspace-controls]' : (isReadOnly ? 'main .atlas-dashboard, main .atlas-path' : (mode.startsWith('experiment') ? '[data-bench]' : '.cm-content'));
    await evaluate(`new Promise((resolve, reject) => { const deadline = Date.now() + 8000; const timer = setInterval(() => { if (document.querySelector(${JSON.stringify(readySelector)})) { clearInterval(timer); resolve(true); } else if (Date.now() > deadline) { clearInterval(timer); reject(new Error('Workspace failed to initialize')); } }, 50); })`);
    if (mode.startsWith('experiment')) {
        // The bench loads its editor when it comes near the viewport, so the
        // page is brought to it before the editor is driven.
        await evaluate(`document.querySelector('[data-bench]').scrollIntoView({ block: 'center' })`);
        await evaluate(`new Promise((resolve) => { const timer = setInterval(() => { if (document.querySelector('.cm-editor') && document.querySelector('#experiment-preview').srcdoc) { clearInterval(timer); resolve(true); } }, 60); setTimeout(() => { clearInterval(timer); resolve(false); }, 10000); })`);
    }
    assert.equal(await evaluate(`document.querySelectorAll('.sort-code').length`), 0);
    if (!waitingForAttempt && !isReadOnly) {
        assert.equal(await evaluate(`document.querySelector('.cm-content').getAttribute('aria-label')`), 'Code editor');
    }
    if (isReadOnly) {
        if (mode === 'dashboard') {
            assert.equal(await evaluate(`document.querySelector('h1').textContent.trim()`), 'Paragraph reflow');
            assert.equal(await evaluate(`document.querySelector('.atlas-dashboard-heading') === null`), true);
            assert.equal(await evaluate(`document.querySelector('.atlas-dashboard-chips') !== null`), true);
            assert.equal(await evaluate(`document.querySelector('.atlas-dashboard-chips').textContent.includes('Coding challenge')`), true);
            assert.equal(await evaluate(`document.querySelector('.atlas-dashboard-chips').textContent.includes('Easy')`), true);
            assert.equal(await evaluate(`document.querySelector('.atlas-dashboard-order-figures').textContent.includes('60')`), true);
            // display voice: the objective heading carries a display size, not body text
            const objectiveSize = await evaluate(`getComputedStyle(document.querySelector('.atlas-dashboard-objective h1')).fontSize`);
            assert.equal(parseFloat(objectiveSize) >= 28, true);
        }
        if (mode === 'learning-path') {
            // The page has no title heading: the course names carry the hierarchy.
            assert.equal(await evaluate(`document.querySelector('main h1') === null`), true);
            assert.equal(await evaluate(`document.querySelectorAll('.atlas-path-course h2').length`), 1);
            assert.equal(await evaluate(`document.querySelectorAll('.atlas-path-course').length`), 1);
            assert.equal(await evaluate(`document.querySelectorAll('.atlas-path-mission').length`), 3);
        }
        for (const [width, height] of [[1920, 1080], [1366, 768], [768, 1024], [390, 844]]) {
            await send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: false });
            await evaluate(`new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)))`);
            assert.equal(await evaluate(`document.documentElement.scrollWidth <= innerWidth`), true);
            if (capture) {
                await mkdir(capture, { recursive: true });
                const shot = await send('Page.captureScreenshot', { format: 'png' });
                await writeFile(join(capture, `${mode}-${width}.png`), Buffer.from(shot.data, 'base64'));
            }
        }
        process.stdout.write(`Chromium ${mode} checks passed
`);
        socket?.close();
        browser.kill('SIGTERM');
        await new Promise((resolve) => { if (browser.exitCode !== null) resolve(); else browser.once('exit', resolve); });
        await new Promise((resolve) => server.close(resolve));
        await rm(directory, { recursive: true, force: true, maxRetries: 3, retryDelay: 100 });
        process.exit(0);
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
    if (capture) {
        assert.ok(['mission-atlas-phase5-review', 'approved-challenge-ui-review'].some(name => capture.startsWith(join(process.cwd(), `storage/app/private/${name}/`))));
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
    } else if (mode.startsWith('experiment')) {
        assert.equal(await evaluate(`document.querySelectorAll('.cm-editor').length`), 1);
        assert.equal(await evaluate(`document.querySelector('#experiment-preview').srcdoc.includes('Try this')`), true);

        // The reference output opens inside the reading column: it must never
        // widen past its column into the bench, and the listing scrolls itself.
        await evaluate(`document.querySelector('.atlas-lesson-reference').open = true`);
        const reference = await evaluate(`(() => {
            const box = document.querySelector('.atlas-lesson-reference').getBoundingClientRect();
            const column = document.querySelector('.atlas-lesson-brief').getBoundingClientRect();
            const bench = document.querySelector('.atlas-lesson-bench').getBoundingClientRect();
            const listing = document.querySelector('.atlas-lesson-reference .log-listing');
            const stacked = bench.y >= box.bottom - 1;
            return {
                boxWidth: Math.round(box.width),
                columnWidth: Math.round(column.width),
                overlaps: !stacked && box.right > bench.x,
                hasListing: listing !== null,
                listingScrolls: listing ? listing.scrollWidth > listing.clientWidth : false,
                result: document.querySelector('#reference-preview').srcdoc,
            };
        })()`);
        assert.equal(reference.boxWidth <= reference.columnWidth + 1, true, 'reference output must stay inside its column');
        assert.equal(reference.overlaps, false, 'reference output must not run under the bench');

        if (mode === 'experiment-result') {
            // A result target: the reference shows the finished work, never the
            // inline styles that produced it.
            assert.equal(reference.hasListing, false, 'a result target shows no markup listing');
            assert.equal(reference.result.includes('DOCTYPE declared'), true, 'the reference renders the expected result');
            assert.equal(await evaluate(`document.querySelectorAll('.atlas-bench form').length`), 0);
        } else {
            assert.equal(reference.hasListing, true, 'a markup target keeps its listing');
            assert.equal(reference.listingScrolls, true, 'a long line of markup scrolls inside the listing');

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
            assert.equal(await evaluate(`document.querySelectorAll('.atlas-bench form[action*="/missions/"]').length`), 0);
            assert.equal(await evaluate(`document.querySelectorAll('.atlas-bench form').length`), 0);
        }
    } else if (mode.startsWith('challenge')) {
        let checks = 0;
        async function check(expression, expected = true) {
            assert.deepEqual(await evaluate(expression), expected, expression);
            checks++;
        }
        async function key(key, code, virtual, modifiers = 0) {
            await send('Input.dispatchKeyEvent', { type: 'keyDown', key, code, modifiers, windowsVirtualKeyCode: virtual, ...(key === 'Enter' ? {text:'\r'} : {}) });
            await send('Input.dispatchKeyEvent', { type: 'keyUp', key, code, modifiers, windowsVirtualKeyCode: virtual });
        }
        async function settle() {
            await evaluate(`new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)))`);
        }
        await send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 900, deviceScaleFactor: 1, mobile: false });
        await settle();
        await check(`document.querySelector('[data-workspace]').dataset.workspaceLayout`, 'tabs');
        await check(`document.querySelector('[data-workspace-pane="preview"]').hidden`);
        await check(`document.querySelector('[data-pane-toggle="brief"]').hidden`);
        await check(`document.querySelector('[data-pane-toggle="editor"]').getAttribute('aria-selected')`, 'true');
        await check(`document.querySelectorAll('.cm-editor').length`, 1);
        await check(`!window.lessonLeak && !document.body.textContent.includes('Private reference solution')`);
        await check(`!document.querySelector('.workspace-actions') && ![...document.querySelectorAll('button')].some(button => button.textContent.trim() === 'Save draft')`);
        await evaluate(`window.originalEditor = CodeQuest.CodeMirror.EditorView.findFromDOM(document.querySelector('.cm-editor')); originalEditor.dispatch({changes: {from: 0, to: originalEditor.state.doc.length, insert: 'unsaved 日本語\\nsecond line'}}); document.querySelector('[data-workspace-resize="brief"]').dispatchEvent(new KeyboardEvent('keydown', {key:'Home', bubbles:true}));`);
        await check(`document.querySelector('[data-workspace-resize="brief"]').getAttribute('aria-valuenow')`, '240');
        await new Promise(resolve => setTimeout(resolve, 600));
        await evaluate(`originalEditor.dispatch({selection: {anchor:3, head:8}}); originalEditor.focus(); window.savedWorkspaceFeedback = document.querySelector('#draft-status').textContent;`);
        await send('Input.insertText', { text: 'changed' });
        const editedSource = await evaluate(`originalEditor.state.doc.toString()`);
        const editedSelection = await evaluate(`({anchor:originalEditor.state.selection.main.anchor, head:originalEditor.state.selection.main.head})`);
        for (const [width,height] of [[1920,1080],[1366,768],[1101,768],[1100,768],[768,1024],[390,844],[320,844],[683,384],[1366,600]]) {
            await send('Emulation.setDeviceMetricsOverride', {width,height,deviceScaleFactor:1,mobile:false});
            await settle();
            await evaluate(`document.querySelector('[data-pane-toggle="editor"]').click(); window.codeBox = document.querySelector('[data-workspace-pane="editor"]').getBoundingClientRect().toJSON(); document.querySelector('[data-pane-toggle="preview"]').click();`);
            await check(`document.querySelector('[data-workspace-pane="preview"]').getBoundingClientRect().width >= 280 && document.querySelector('[data-workspace-pane="preview"]').getBoundingClientRect().height >= 100`);
            await check(`document.querySelector('[data-workspace-pane="editor"]').hidden && !document.querySelector('[data-workspace-pane="preview"]').hidden`);
            await check(`['x','y','width','height'].every(key => Math.abs(codeBox[key] - document.querySelector('[data-workspace-pane="preview"]').getBoundingClientRect()[key]) <= 1)`);
            await check(`CodeQuest.CodeMirror.EditorView.findFromDOM(document.querySelector('.cm-editor')) === originalEditor`);
            await check(`originalEditor.state.doc.toString()`, editedSource);
            await check(`({anchor:originalEditor.state.selection.main.anchor, head:originalEditor.state.selection.main.head})`, editedSelection);
            await check(`document.querySelector('#draft-status').textContent === savedWorkspaceFeedback`);
            await check(`document.documentElement.scrollWidth <= innerWidth`);
            if (width > 1100) await check(`!document.querySelector('[data-workspace-pane="brief"]').hidden`);
            await evaluate(`document.querySelector('[data-pane-toggle="editor"]').click()`);
        }
        await evaluate(`originalEditor.focus()`);
        await key('z', 'KeyZ', 90, 2);
        await check(`originalEditor.state.doc.toString()`, 'unsaved 日本語\nsecond line');
        await check(`({anchor:originalEditor.state.selection.main.anchor,head:originalEditor.state.selection.main.head})`, {anchor:3,head:8});
        await evaluate(`document.querySelector('[data-pane-toggle="editor"]').focus()`);
        await key('ArrowRight', 'ArrowRight', 39);
        await check(`document.activeElement.id`, 'workspace-tab-preview');
        await check(`document.querySelector('[data-pane-toggle="preview"]').getAttribute('aria-selected')`, 'true');
        await key('Home', 'Home', 36);
        await check(`document.activeElement.id`, 'workspace-tab-editor');
        await send('Emulation.setDeviceMetricsOverride', {width:390,height:844,deviceScaleFactor:1,mobile:false});
        await settle();
        await evaluate(`document.querySelector('[data-pane-toggle="editor"]').focus()`);
        await key('Home', 'Home', 36);
        await check(`document.activeElement.id`, 'workspace-tab-brief');
        await check(`!document.querySelector('[data-workspace-pane="brief"]').hidden`);
        await check(`document.querySelector('#instructions-title').textContent`, 'Lesson context');
        await key('End', 'End', 35);
        await check(`document.activeElement.id`, 'workspace-tab-preview');
        await evaluate(`document.querySelector('[data-pane-toggle="editor"]').click(); originalEditor.dispatch({changes:{from:0,to:originalEditor.state.doc.length,insert:'<h1>Live preview</h1>'}}); window.beforeRun = document.querySelector('#preview-frame').srcdoc; document.querySelector('#run').focus();`);
        await check(`document.activeElement.id`, 'run');
        await key('Enter', 'Enter', 13);
        await check(`document.querySelector('[data-challenge-confirm]').open`);
        await check(`document.activeElement.value`, 'cancel');
        await check(`document.querySelector('#preview-frame').srcdoc === beforeRun`);
        await evaluate(`document.querySelector('[data-challenge-confirm] button[value="cancel"]').click()`);
        await settle();
        await check(`document.activeElement.id`, 'run');
        await check(`document.querySelector('#preview-frame').srcdoc === beforeRun`);
        await evaluate(`document.querySelector('#run').click()`);
        await key('Escape', 'Escape', 27);
        await settle();
        await check(`!document.querySelector('[data-challenge-confirm]').open && document.querySelector('#preview-frame').srcdoc === beforeRun`);
        await evaluate(`document.querySelector('#run').click(); document.querySelector('[data-confirm-action]').click()`);
        await settle();
        await check(`!document.querySelector('[data-workspace-pane="preview"]').hidden`);
        await check(`document.querySelector('#preview-frame').srcdoc.includes('<h1>Live preview</h1>')`);
        await check(`document.querySelector('#preview-frame').getAttribute('sandbox')`, '');
        if (capture) {
            for (const [width,height] of [[1920,1080],[1366,768],[768,1024],[390,844]]) {
                await send('Emulation.setDeviceMetricsOverride', {width,height,deviceScaleFactor:1,mobile:false});
                await settle();
                const shot = await send('Page.captureScreenshot', {format:'png'});
                await writeFile(join(capture, `${mode}-preview-${width}.png`), Buffer.from(shot.data,'base64'));
            }
        }
        await evaluate(`document.querySelector('[data-challenge-action="submit"] button').click()`);
        await check(`document.querySelector('[data-challenge-confirm]').open`);
        await check(`document.querySelector('#challenge-confirm-copy').textContent.includes('XP')`);
        await check(`document.querySelector('[data-challenge-action="submit"] .code-payload').value`, '<h1>Live preview</h1>');
        await evaluate(`document.querySelector('[data-challenge-confirm] button[value="cancel"]').click()`);
        await settle();
        assert.equal(submissions, 0);
        if (await evaluate(`Boolean(document.querySelector('[data-challenge-action="hint"]'))`)) {
            await evaluate(`document.querySelector('[data-challenge-action="hint"] button').click()`);
            await check(`document.querySelector('[data-challenge-confirm]').open`);
            await evaluate(`document.querySelector('[data-challenge-confirm] button[value="cancel"]').click()`);
            await settle();
        }
        await evaluate(`document.querySelector('[data-pane-toggle="editor"]').click(); document.querySelector('#draft-form').addEventListener('submit', event => {event.preventDefault(); window.shortcutPayload = new FormData(event.target).get('code');}); originalEditor.focus();`);
        await key('s', 'KeyS', 83, 2);
        await check(`window.shortcutPayload`, '<h1>Live preview</h1>');
        await check(`CodeQuest.CodeMirror.EditorView.findFromDOM(document.querySelector('.cm-editor')) === originalEditor`);
        await evaluate(`originalEditor.focus()`);
        await key('Escape','Escape',27);
        await key('Tab','Tab',9);
        await check(`!document.querySelector('.cm-editor').contains(document.activeElement)`);
        await send('Emulation.setDeviceMetricsOverride', {width:1366,height:768,deviceScaleFactor:1,mobile:false});
        await settle();
        await evaluate(`document.querySelector('[data-workspace-options] summary').click(); document.querySelector('[data-workspace-hide-brief]').click()`);
        await check(`document.querySelector('[data-workspace-pane="brief"]').hidden`);
        await check(`document.querySelector('[data-workspace-hide-icon="open"]').hidden === false && document.querySelector('[data-workspace-hide-icon="closed"]').hidden === true`);
        await evaluate(`document.querySelector('[data-workspace-reset]').click()`);
        await check(`!document.querySelector('[data-workspace-pane="brief"]').hidden && document.querySelector('[data-workspace-pane="preview"]').hidden`);
        await check(`document.querySelector('[data-workspace-hide-icon="closed"]').hidden === false && document.querySelector('[data-workspace-hide-icon="open"]').hidden === true`);
        await key('Escape','Escape',27);
        await check(`document.activeElement === document.querySelector('[data-workspace-options] summary')`);
        assert.equal(submissions, 0);
        await evaluate(`document.querySelector('[data-workspace-options] summary').click(); document.querySelector('[data-workspace-assistance-open]').click()`);
        await check(`document.querySelector('[data-challenge-assistance]').open`);
        await evaluate(`document.querySelector('[data-challenge-assistance] form[method="dialog"] button').click()`);
        await settle();
        await check(`document.activeElement === document.querySelector('[data-workspace-options] summary')`);
        await evaluate(`document.querySelector('[data-workspace-options] summary').click()`);
        await send('Runtime.evaluate', {expression:`document.querySelector('[data-workspace-fullscreen]').click()`, userGesture:true});
        await evaluate(`new Promise((resolve,reject) => {const deadline=Date.now()+3000;const timer=setInterval(() => {if(document.fullscreenElement){clearInterval(timer);resolve(true);}else if(Date.now()>deadline){clearInterval(timer);reject(new Error('Fullscreen did not open'));}},30);})`);
        await check(`Boolean(document.fullscreenElement)`);
        await check(`document.querySelector('[data-workspace-fullscreen-icon="exit"]').hidden === false && document.querySelector('[data-workspace-fullscreen-icon="enter"]').hidden === true`);
        await check(`document.querySelector('[data-workspace-fullscreen-label]').textContent`, 'Exit fullscreen');
        await evaluate(`document.querySelector('[data-workspace-options] summary').click(); document.querySelector('[data-workspace-fullscreen]').click()`);
        await evaluate(`new Promise((resolve,reject) => {const deadline=Date.now()+3000;const timer=setInterval(() => {if(!document.fullscreenElement){clearInterval(timer);resolve(true);}else if(Date.now()>deadline){clearInterval(timer);reject(new Error('Fullscreen did not exit'));}},30);})`);
        await check(`!document.fullscreenElement && CodeQuest.CodeMirror.EditorView.findFromDOM(document.querySelector('.cm-editor')) === originalEditor`);
        await check(`document.querySelector('[data-workspace-fullscreen-icon="enter"]').hidden === false && document.querySelector('[data-workspace-fullscreen-icon="exit"]').hidden === true`);
        process.stdout.write(`Challenge ${mode}: ${checks} tabbed-workspace checks passed\n`);
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
