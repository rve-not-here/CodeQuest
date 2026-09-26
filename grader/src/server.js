import { spawn } from 'node:child_process';
import { createServer } from 'node:http';
import { timingSafeEqual } from 'node:crypto';

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
    if (!body || typeof body.source !== 'string' || body.source === '' || Buffer.byteLength(body.source) > MAX_BODY_BYTES) {
        return false;
    }
    if (!Array.isArray(body.tests) || body.tests.length === 0 || body.tests.length > 50) {
        return false;
    }
    return body.tests.every((test) => test && (test.type === 'function' || test.type === 'console') && test.payload !== undefined);
}

// Fixed argument vector. No user-controlled data ever reaches argv;
// the payload travels over stdin as JSON.
function runtimeArgs() {
    return [
        'run', '--rm', '-i',
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

function gradeInSandbox(payload) {
    const started = Date.now();
    return new Promise((resolve) => {
        const child = spawn(RUNTIME, runtimeArgs(), { stdio: ['pipe', 'pipe', 'ignore'] });
        let stdout = '';
        let settled = false;
        const finish = (result) => {
            if (!settled) {
                settled = true;
                resolve(result);
            }
        };
        const timer = setTimeout(() => {
            child.kill('SIGKILL');
            finish({ status: 'error', tests_total: 0, tests_passed: 0, duration_ms: Date.now() - started, error_type: 'timeout' });
        }, EXEC_TIMEOUT_MS);
        timer.unref?.();

        child.stdout.on('data', (chunk) => {
            stdout += chunk.toString('utf8');
            if (Buffer.byteLength(stdout) > MAX_STDOUT_BYTES) {
                child.kill('SIGKILL');
                finish({ status: 'error', tests_total: 0, tests_passed: 0, duration_ms: Date.now() - started, error_type: 'resource_limit' });
            }
        });
        child.on('error', () => {
            clearTimeout(timer);
            finish({ status: 'error', tests_total: 0, tests_passed: 0, duration_ms: Date.now() - started, error_type: 'grader_unavailable' });
        });
        child.on('close', () => {
            clearTimeout(timer);
            try {
                const parsed = JSON.parse(stdout);
                if (parsed && ['passed', 'failed', 'error'].includes(parsed.status)
                    && Number.isInteger(parsed.tests_total) && Number.isInteger(parsed.tests_passed)) {
                    finish({
                        status: parsed.status,
                        tests_total: parsed.tests_total,
                        tests_passed: parsed.tests_passed,
                        duration_ms: Date.now() - started,
                        error_type: typeof parsed.error_type === 'string' ? parsed.error_type : null,
                    });
                    return;
                }
            } catch {
                // Fall through to invalid response below.
            }
            finish({ status: 'error', tests_total: 0, tests_passed: 0, duration_ms: Date.now() - started, error_type: 'invalid_response' });
        });

        try {
            child.stdin.write(JSON.stringify({ ...payload, exec_timeout_ms: EXEC_TIMEOUT_MS }));
            child.stdin.end();
        } catch {
            child.kill('SIGKILL');
            finish({ status: 'error', tests_total: 0, tests_passed: 0, duration_ms: Date.now() - started, error_type: 'grader_unavailable' });
        }
    });
}

function readBody(request) {
    return new Promise((resolve, reject) => {
        let size = 0;
        const chunks = [];
        request.on('data', (chunk) => {
            size += chunk.length;
            if (size > MAX_BODY_BYTES) {
                reject(new Error('body too large'));
                request.destroy();
                return;
            }
            chunks.push(chunk);
        });
        request.on('end', () => resolve(Buffer.concat(chunks).toString('utf8')));
        request.on('error', reject);
    });
}

export function createApp() {
    return createServer(async (request, response) => {
        const json = (code, body) => {
            response.writeHead(code, { 'content-type': 'application/json' });
            response.end(JSON.stringify(body));
        };

        if (request.method === 'GET' && request.url === '/healthz') {
            json(200, { ok: true });
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

        json(200, await gradeInSandbox({ source: body.source, tests: body.tests }));
    });
}

const server = createApp();
server.listen(PORT, HOST, () => {
    process.stdout.write(`codequest-js-grader listening on ${HOST}:${PORT} (runtime: ${RUNTIME})\n`);
});
