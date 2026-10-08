import { spawn } from 'node:child_process';
import { createServer } from 'node:http';
import { randomUUID, timingSafeEqual } from 'node:crypto';
import { fileURLToPath } from 'node:url';
import { basename } from 'node:path';
import { evaluate, executionPayload, result } from './evaluator.js';

// CodeQuest JS grader service. Internal infrastructure only: binds
// loopback by default, requires a bearer token, and runs every submission
// in a fresh ephemeral rootless container. This process never executes
// student code itself and never talks to a container socket.

const PORT = Number.parseInt(process.env.GRADER_PORT ?? '4317', 10);
const HOST = process.env.GRADER_HOST ?? '127.0.0.1';
const TOKEN = process.env.GRADER_TOKEN ?? '';
const RUNTIME = process.env.GRADER_RUNTIME ?? 'podman';
const RUNNER_IMAGE = process.env.GRADER_RUNNER_IMAGE ?? 'codequest-js-runner:1.0';
const EXEC_TIMEOUT_MS = Number.parseInt(process.env.GRADER_EXEC_TIMEOUT_MS ?? '10000', 10);
const MAX_BODY_BYTES = 256 * 1024;
const MAX_STDOUT_BYTES = 256 * 1024;
const MAX_SOURCE_BYTES = 65536;
const configuredConcurrency = Number(process.env.GRADER_MAX_CONCURRENT ?? '4');
const MAX_CONCURRENT = Number.isInteger(configuredConcurrency) && configuredConcurrency > 0 && configuredConcurrency <= 64 ? configuredConcurrency : 4;

function authorized(request) {
    if (TOKEN === '') {
        return false;
    }
    const header = request.headers.authorization ?? '';
    const presented = header.startsWith('Bearer ') ? header.slice(7) : '';
    const expected = Buffer.from(TOKEN);
    const actual = Buffer.from(presented);
    return expected.length === actual.length && timingSafeEqual(expected, actual);
}

function validPayload(body) {
    if (!body || typeof body.source !== 'string' || body.source === '' || Buffer.byteLength(body.source) > MAX_SOURCE_BYTES) {
        return false;
    }
    if (!Array.isArray(body.tests) || body.tests.length === 0 || body.tests.length > 50) {
        return false;
    }
    try {
        executionPayload(body);
        return true;
    } catch {
        return false;
    }
}

// Fixed argument vector. No user-controlled data ever reaches argv;
// the payload travels over stdin as JSON.
function runtimeArgs(containerName) {
    return [
        'run', '--rm', '-i', '--name', containerName,
        '--network', 'none',
        '--user', '65534:65534',
        '--read-only',
        '--tmpfs', '/tmp:rw,size=16m,noexec',
        '--cap-drop', 'ALL',
        '--security-opt', 'no-new-privileges',
        '--pids-limit', '32',
        '--cpus', '0.5',
        '--memory', '128m',
        '--memory-swap', '128m',
        RUNNER_IMAGE,
        'node', '/app/src/runner.js',
    ];
}

function removeContainer(containerName) {
    return new Promise((resolve, reject) => {
        const docker = basename(RUNTIME) === 'docker';
        const args = docker ? ['rm', '--force', containerName] : ['rm', '--force', '--time', '0', '--ignore', containerName];
        const cleanup = spawn(RUNTIME, args, { stdio: ['ignore', 'ignore', 'pipe'] });
        let stderr = '';
        cleanup.stderr.setEncoding('utf8');
        cleanup.stderr.on('data', chunk => { stderr = (stderr + chunk).slice(0, 4096); });
        const timer = setTimeout(() => {
            cleanup.kill('SIGKILL');
            reject(new Error('container cleanup timeout'));
        }, 5000);
        timer.unref?.();
        cleanup.once('error', error => { clearTimeout(timer); reject(error); });
        cleanup.once('close', code => {
            clearTimeout(timer);
            const absent = docker && code === 1 && stderr.trim() === `Error response from daemon: No such container: ${containerName}`;
            if (code === 0 || absent) {
                resolve();
            } else {
                reject(new Error('container cleanup failed'));
            }
        });
    });
}

export function gradeInSandbox(payload, spawnSandbox, cleanupSandbox) {
    const started = Date.now();
    const containerName = `codequest-grade-${randomUUID()}`;
    const start = spawnSandbox ?? (() => spawn(RUNTIME, runtimeArgs(containerName), { stdio: ['pipe', 'pipe', 'ignore'] }));
    const cleanup = cleanupSandbox ?? (spawnSandbox ? async () => {} : removeContainer);
    return new Promise((resolve) => {
        let child;
        let stdout = '';
        let settled = false;
        let timer;
        const finish = async (outcome, aborted = false) => {
            if (settled) {
                return;
            }
            settled = true;
            clearTimeout(timer);
            if (aborted) {
                child?.kill('SIGKILL');
                try {
                    await cleanup(containerName);
                } catch {
                    outcome = result('error', 0, 0, 'grader_unavailable', Date.now() - started);
                }
            }
            resolve(outcome);
        };
        try {
            child = start(containerName);
        } catch {
            finish(result('error', 0, 0, 'grader_unavailable', Date.now() - started), true);
            return;
        }
        timer = setTimeout(() => {
            finish(result('error', 0, 0, 'timeout', Date.now() - started), true);
        }, EXEC_TIMEOUT_MS);
        timer.unref?.();

        child.stdout.setEncoding('utf8');
        child.stdout.on('data', (chunk) => {
            stdout += chunk;
            if (Buffer.byteLength(stdout) > MAX_STDOUT_BYTES) {
                finish(result('error', 0, 0, 'resource_limit', Date.now() - started), true);
            }
        });
        child.stdin.on('error', () => {
            finish(result('error', 0, 0, 'grader_unavailable', Date.now() - started), true);
        });
        child.on('error', () => {
            finish(result('error', 0, 0, 'grader_unavailable', Date.now() - started), true);
        });
        child.on('close', code => {
            if (code !== 0) {
                finish(result('error', 0, 0, 'grader_unavailable', Date.now() - started), true);
                return;
            }
            finish(evaluate(payload, stdout, Date.now() - started));
        });

        try {
            child.stdin.write(JSON.stringify(executionPayload(payload, EXEC_TIMEOUT_MS)));
            child.stdin.end();
        } catch {
            finish(result('error', 0, 0, 'grader_unavailable', Date.now() - started), true);
        }
    });
}

function readBody(request) {
    return new Promise((resolve, reject) => {
        let size = 0;
        const chunks = [];
        const timer = setTimeout(() => {
            reject(new Error('body timeout'));
            request.destroy();
        }, 5000);
        timer.unref?.();
        request.on('data', (chunk) => {
            size += chunk.length;
            if (size > MAX_BODY_BYTES) {
                clearTimeout(timer);
                reject(new Error('body too large'));
                request.destroy();
                return;
            }
            chunks.push(chunk);
        });
        request.on('end', () => {
            clearTimeout(timer);
            resolve(Buffer.concat(chunks).toString('utf8'));
        });
        request.on('error', error => { clearTimeout(timer); reject(error); });
        request.on('aborted', () => { clearTimeout(timer); reject(new Error('request aborted')); });
    });
}

export function createApp(spawnSandbox, cleanupSandbox) {
    let active = 0;
    let cleanupFailed = false;
    const cleanup = cleanupSandbox ?? (spawnSandbox ? async () => {} : removeContainer);
    const cleanupExecution = async containerName => {
        try {
            await cleanup(containerName);
        } catch (error) {
            cleanupFailed = true;
            throw error;
        }
    };
    return createServer(async (request, response) => {
        const json = (code, body) => {
            response.writeHead(code, { 'content-type': 'application/json' });
            response.end(JSON.stringify(body));
        };

        if (request.method === 'GET' && request.url === '/healthz') {
            json(cleanupFailed ? 503 : 200, { ok: !cleanupFailed });
            return;
        }

        if (request.method !== 'POST' || request.url !== '/grade') {
            json(404, { error: 'not found' });
            return;
        }

        if (!authorized(request)) {
            json(401, { error: 'unauthorized' });
            return;
        }

        if (cleanupFailed || active >= MAX_CONCURRENT) {
            response.setHeader('retry-after', '1');
            json(503, { error: 'grader_unavailable' });
            return;
        }

        active++;
        try {
            let body;
            try {
                body = JSON.parse(await readBody(request));
            } catch {
                json(400, { error: 'invalid payload' });
                return;
            }

            if (!validPayload(body)) {
                json(400, { error: 'invalid payload' });
                return;
            }

            json(200, await gradeInSandbox({ source: body.source, tests: body.tests }, spawnSandbox, cleanupExecution));
        } finally {
            active--;
        }
    });
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
    const server = createApp();
    server.listen(PORT, HOST, () => {
        process.stdout.write(`codequest-js-grader listening on ${HOST}:${PORT} (runtime: ${RUNTIME})\n`);
    });
}
