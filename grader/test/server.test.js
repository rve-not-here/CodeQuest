import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import assert from 'node:assert/strict';
import { PassThrough } from 'node:stream';
import { EventEmitter } from 'node:events';
import { mkdtemp, writeFile, readFile, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

process.env.GRADER_TOKEN = 'internal-test-token';
process.env.GRADER_MAX_CONCURRENT = '1';
const { createApp, gradeInSandbox } = await import('../src/server.js');
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

test('aborted execution removes its container before grading resolves', async () => {
    const child = new EventEmitter();
    child.stdin = new PassThrough();
    child.stdout = new PassThrough();
    let killed = false;
    child.kill = () => { killed = true; child.emit('close', null); };
    let removedName;
    let release;
    const cleanup = new Promise(resolve => { release = resolve; });
    let settled = false;
    const grading = gradeInSandbox({ source: 'function add(){return 0}', tests }, () => child, async name => {
        removedName = name;
        await cleanup;
    }).then(value => { settled = true; return value; });
    child.stdout.write('x'.repeat(256 * 1024 + 1));
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(killed, true);
    assert.match(removedName ?? '', /^codequest-grade-[0-9a-f-]{36}$/);
    assert.equal(settled, false);
    release();
    const body = await grading;
    assert.equal(body.status, 'error');
    assert.equal(body.error_type, 'resource_limit');
});

test('cleanup failure closes service capacity and health until restart', async () => {
    let executions = 0;
    const server = createApp(() => {
        executions++;
        const child = new EventEmitter();
        child.stdin = new PassThrough();
        child.stdout = new PassThrough();
        child.kill = () => {};
        setImmediate(() => child.stdout.write('x'.repeat(256 * 1024 + 1)));
        return child;
    }, async () => { throw new Error('runtime cleanup failed'); });
    await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
    const url = `http://127.0.0.1:${server.address().port}`;
    try {
        const first = await post(`${url}/grade`, 'function add(){return 0}');
        assert.equal((await first.json()).error_type, 'grader_unavailable');
        assert.equal((await post(`${url}/grade`, 'function add(){return 0}')).status, 503);
        assert.equal(executions, 1);
        assert.equal((await fetch(`${url}/healthz`)).status, 503);
    } finally {
        await new Promise(resolve => server.close(resolve));
    }
});

for (const [runtime, missing] of [['podman', true], ['docker', true], ['docker', false]]) {
    test(`${runtime} cleanup ${missing ? 'tolerates an already removed container using supported arguments' : 'fails closed on a daemon error'}`, async () => {
        const directory = await mkdtemp(join(tmpdir(), 'codequest-cleanup-'));
        const executable = join(directory, runtime);
        const log = join(directory, 'arguments.jsonl');
        await writeFile(executable, `#!${process.execPath}
import { appendFileSync } from 'node:fs';
const args = process.argv.slice(2);
appendFileSync(${JSON.stringify(log)}, JSON.stringify(args) + '\\n');
if (args[0] === 'run') {
    process.stdout.write('x'.repeat(256 * 1024 + 1));
    setInterval(() => {}, 1000);
} else {
    const name = args.at(-1);
    if (${JSON.stringify(runtime)} === 'podman' && args.includes('--ignore')) {
        process.exit(0);
    }
    if (${JSON.stringify(runtime)} === 'docker' && !args.includes('--time')) {
        process.stderr.write(${missing ? "'Error response from daemon: No such container: ' + name" : "'Cannot connect to the Docker daemon'"});
        process.exit(1);
    }
    process.stderr.write('unsupported argument or missing container');
    process.exit(125);
}
`, { mode: 0o700 });
        try {
            const module = new URL('../src/server.js', import.meta.url).href;
            const source = `import { gradeInSandbox } from ${JSON.stringify(module)}; console.log(JSON.stringify(await gradeInSandbox(${JSON.stringify({ source: 'function add(){return 0}', tests })})));`;
            const child = spawn(process.execPath, ['--input-type=module', '-e', source], {
                env: { ...process.env, GRADER_RUNTIME: executable }, stdio: ['ignore', 'pipe', 'pipe'],
            });
            let output = '';
            child.stdout.on('data', chunk => { output += chunk; });
            const code = await new Promise(resolve => child.on('close', resolve));
            assert.equal(code, 0);
            assert.equal(JSON.parse(output).error_type, missing ? 'resource_limit' : 'grader_unavailable');
            const calls = (await readFile(log, 'utf8')).trim().split('\n').map(line => JSON.parse(line));
            assert.equal(calls.length, 2);
            assert.equal(calls[1][0], 'rm');
            assert.equal(calls[1].at(-1), calls[0][calls[0].indexOf('--name') + 1]);
            assert.equal(calls[1].includes('--time'), runtime === 'podman');
            assert.equal(calls[1].includes('--ignore'), runtime === 'podman');
        } finally {
            await rm(directory, { recursive: true, force: true });
        }
    });
}
