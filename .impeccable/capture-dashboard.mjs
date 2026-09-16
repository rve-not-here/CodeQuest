import fs from 'node:fs';
import WebSocket from 'ws';

const [, , debuggerUrl, applicationUrl, outputPath] = process.argv;

if (!debuggerUrl || !applicationUrl || !outputPath) {
    throw new Error('Usage: node capture-dashboard.mjs <debugger-url> <application-url> <output-path>');
}

async function waitForDebugger() {
    for (let attempt = 0; attempt < 40; attempt += 1) {
        try {
            const response = await fetch(`${debuggerUrl}/json`);
            const targets = await response.json();
            const page = targets.find((target) => target.type === 'page');

            if (page?.webSocketDebuggerUrl) {
                return page.webSocketDebuggerUrl;
            }
        } catch {
            // Chromium may still be starting.
        }

        await new Promise((resolve) => setTimeout(resolve, 250));
    }

    throw new Error('Chromium debugger did not become ready.');
}

const socket = new WebSocket(await waitForDebugger());
const pending = new Map();
let commandId = 0;

await new Promise((resolve, reject) => {
    socket.once('open', resolve);
    socket.once('error', reject);
});

socket.on('message', (data) => {
    const message = JSON.parse(data.toString());
    const callback = pending.get(message.id);

    if (callback) {
        pending.delete(message.id);
        callback(message);
    }
});

function send(method, params = {}) {
    commandId += 1;

    return new Promise((resolve, reject) => {
        pending.set(commandId, (message) => {
            if (message.error) {
                reject(new Error(message.error.message));
                return;
            }

            resolve(message.result);
        });

        socket.send(JSON.stringify({ id: commandId, method, params }));
    });
}

async function waitFor(condition, timeout = 10000) {
    const startedAt = Date.now();

    while (Date.now() - startedAt < timeout) {
        const result = await send('Runtime.evaluate', {
            expression: condition,
            returnByValue: true,
        });

        if (result.result.value) {
            return;
        }

        await new Promise((resolve) => setTimeout(resolve, 100));
    }

    throw new Error(`Timed out waiting for: ${condition}`);
}

await send('Page.enable');
await send('Runtime.enable');
await send('Emulation.setDeviceMetricsOverride', {
    width: 1586,
    height: 992,
    deviceScaleFactor: 1,
    mobile: false,
});
await send('Page.navigate', { url: `${applicationUrl}/login` });
await waitFor("document.readyState === 'complete' && document.querySelector('#username') !== null");

await send('Runtime.evaluate', {
    expression: `
        document.querySelector('#username').value = 'test';
        document.querySelector('#password').value = 'password';
        document.querySelector('form').requestSubmit();
    `,
});

await waitFor("location.pathname === '/dashboard' && document.readyState === 'complete'", 15000);
await new Promise((resolve) => setTimeout(resolve, 600));

const screenshot = await send('Page.captureScreenshot', {
    format: 'png',
    captureBeyondViewport: false,
    fromSurface: true,
});

fs.writeFileSync(outputPath, Buffer.from(screenshot.data, 'base64'));
socket.close();
