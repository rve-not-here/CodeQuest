import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import assert from 'node:assert/strict';

process.env.GRADER_TOKEN = 'internal-test-token';
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
