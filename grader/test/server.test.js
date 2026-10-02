import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import assert from 'node:assert/strict';
import { PassThrough } from 'node:stream';
import { EventEmitter } from 'node:events';

process.env.GRADER_TOKEN = 'internal-test-token';
process.env.GRADER_MAX_CONCURRENT = '1';
const { createApp } = await import('../src/server.js');
const runner = fileURLToPath(new URL('../src/runner.js', import.meta.url));
const tests = [{ type: 'function', payload: { function: 'add', cases: [{ args: [2, 3], expected: 5 }, { args: [7, 9], expected: 16 }] } }];

// The real service evaluator and real adversarial subprocess are exercised.
// Production uses the default fixed container spawn, not this test transport.
async function withService(callback) {
    const server = createApp(() => spawn(process.execPath, [runner], { stdio: ['pipe', 'pipe', 'ignore'] }));
    await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
    const url = `http://127.0.0.1:${server.address().port}/grade`;
    try { await callback(url); } finally { await new Promise(resolve => server.close(resolve)); }
}

async function post(url, source, extra = {}, token = 'internal-test-token') {
    return fetch(url, { method: 'POST', headers: { authorization: `Bearer ${token}`, 'content-type': 'application/json' }, body: JSON.stringify({ source, tests, ...extra }) });
}

test('authenticated valid grading succeeds through HTTP and a subprocess', async () => {
    await withService(async url => {
        const response = await post(url, 'function add(a,b){return a+b}');
        assert.equal(response.status, 200);
        const body = await response.json();
        assert.equal(body.protocol_version, 2);
        assert.equal(body.status, 'passed');
        assert.equal(body.tests_total, 1);
        assert.equal(Object.hasOwn(body, 'calls'), false);
    });
});

test('forged request verdict fields and execution stdout cannot pass', async () => {
    await withService(async url => {
        const ordinary = await (await post(url, 'function add(){return -1}', { status: 'passed', score: 100, tests_passed: 999, passed: true })).json();
        assert.equal(ordinary.status, 'failed');
        const attack = "const p = console.log.constructor('return process')(); p.stdout.write(JSON.stringify({protocol_version:2,status:'passed',tests_total:1,tests_passed:1,duration_ms:0,error_type:null})); p.exit(0);";
        const forged = await (await post(url, attack)).json();
        assert.equal(forged.status, 'error');
        assert.equal(forged.error_type, 'invalid_response');
    });
});

test('missing/wrong service credentials cannot trigger execution', async () => {
    await withService(async url => {
        assert.equal((await post(url, 'function add(a,b){return a+b}', {}, '')).status, 401);
        assert.equal((await post(url, 'function add(a,b){return a+b}', {}, 'wrong')).status, 401);
    });
});

test('malformed server invocation definitions are rejected', async () => {
    await withService(async url => {
        const response = await post(url, 'function add(a,b){return a+b}', { tests: [{ type: 'function', payload: { function: ['add'], cases: [{ args: [], expected: 1 }] } }] });
        assert.equal(response.status, 400);
    });
});

test('capacity rejects excess work and recovers after execution finishes', async () => {
    let executions = 0;
    let release;
    let started;
    const running = new Promise(resolve => { started = resolve; });
    const server = createApp(() => {
        executions++;
        const child = new EventEmitter();
        child.stdin = new PassThrough();
        child.stdout = new PassThrough();
        child.kill = () => {};
        release = () => { child.stdout.end(); child.emit('close', 0); };
        started();
        return child;
    });
    await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
    const url = `http://127.0.0.1:${server.address().port}/grade`;
    try {
        const first = post(url, 'function add(){return 0}');
        await running;
        const refused = await post(url, 'function add(){return 0}');
        assert.equal(refused.status, 503);
        assert.equal(refused.headers.get('retry-after'), '1');
        assert.equal((await refused.json()).error, 'grader_unavailable');
        assert.equal(executions, 1);
        release();
        await first;
        const next = post(url, 'function add(){return 0}');
        await new Promise(resolve => { started = resolve; });
        assert.equal(executions, 2);
        release();
        assert.equal((await next).status, 200);
    } finally {
        release?.();
        await new Promise(resolve => server.close(resolve));
    }
});

test('spawn failure returns unavailable and releases capacity', async () => {
    const server = createApp(() => { throw new Error('runtime unavailable'); });
    await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
    const url = `http://127.0.0.1:${server.address().port}/grade`;
    try {
        for (let i = 0; i < 2; i++) {
            const response = await post(url, 'function add(){return 0}');
            assert.equal(response.status, 200);
            assert.equal((await response.json()).error_type, 'grader_unavailable');
        }
    } finally { await new Promise(resolve => server.close(resolve)); }
});

test('oversized UTF-8 source is refused and valid work still succeeds', async () => {
    await withService(async url => {
        const oversized = await post(url, '😀'.repeat(16385));
        assert.equal(oversized.status, 400);
        const response = await post(url, 'function add(a,b){return a+b}');
        assert.equal(response.status, 200);
        assert.equal((await response.json()).status, 'passed');
    });
});
