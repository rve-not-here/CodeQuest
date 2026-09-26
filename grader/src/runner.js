import vm from 'node:vm';

// CodeQuest isolated JS runner. Reads one grading payload from stdin,
// evaluates the student source inside a bare VM context, runs the hidden
// tests, and prints exactly one JSON result line to stdout.
//
// The VM context is evaluation ergonomics and harness isolation only.
// The container this file runs in is the security boundary: no network,
// non-root user, read-only root filesystem, dropped capabilities,
// PID/CPU/memory limits, and a hard wall-clock timeout enforced outside.

const MAX_OUTPUT_ENTRIES = 200;
const MAX_STRING_CHARS = 1000;
const MAX_ARRAY_ITEMS = 100;
const MAX_DEPTH = 12;

function sanitize(value, depth = 0) {
    if (value === undefined) {
        return { __undefined: true };
    }
    if (value === null || typeof value === 'boolean') {
        return value;
    }
    if (typeof value === 'number') {
        if (Number.isNaN(value)) {
            return { __nan: true };
        }
        return value;
    }
    if (typeof value === 'string') {
        return value.length > MAX_STRING_CHARS ? value.slice(0, MAX_STRING_CHARS) : value;
    }
    if (typeof value === 'function') {
        return '[function]';
    }
    if (typeof value === 'bigint') {
        return `[bigint:${value.toString().slice(0, 64)}]`;
    }
    if (typeof value === 'symbol') {
        return '[symbol]';
    }
    if (depth > MAX_DEPTH || typeof value !== 'object') {
        return '[unserializable]';
    }
    if (Array.isArray(value)) {
        return value.slice(0, MAX_ARRAY_ITEMS).map((item) => sanitize(item, depth + 1));
    }
    const out = {};
    for (const key of Object.keys(value).slice(0, MAX_ARRAY_ITEMS)) {
        out[key] = sanitize(value[key], depth + 1);
    }
    return out;
}

function canonical(value) {
    return JSON.stringify(value, (key, item) => {
        if (item !== null && typeof item === 'object' && !Array.isArray(item)) {
            const sorted = {};
            for (const name of Object.keys(item).sort()) {
                sorted[name] = item[name];
            }
            return sorted;
        }
        return item;
    });
}

function deepEqual(actual, expected) {
    const left = sanitize(actual);
    const right = sanitize(expected);
    if (typeof left === 'number' && typeof right === 'number') {
        return Object.is(left, right);
    }
    return typeof left === typeof right && canonical(left) === canonical(right);
}

function makeContext(calls) {
    const record = (...args) => {
        if (calls.length < MAX_OUTPUT_ENTRIES) {
            calls.push(args.map((arg) => sanitize(arg)));
        }
    };
    const sandbox = {
        console: { log: record, info: record },
    };
    const context = vm.createContext(sandbox);
    // Freeze the harness surface so student code cannot redefine capture.
    Object.freeze(sandbox.console);
    return context;
}

async function withDeadline(promise, ms, label) {
    let timer;
    const guard = new Promise((_, reject) => {
        timer = setTimeout(() => reject(new Error(label)), ms);
        timer.unref?.();
    });
    try {
        return await Promise.race([promise, guard]);
    } finally {
        clearTimeout(timer);
    }
}

async function runPayload(payload, execTimeoutMs) {
    const started = Date.now();
    const finish = (status, testsTotal, testsPassed, errorType) => ({
        status,
        tests_total: testsTotal,
        tests_passed: testsPassed,
        duration_ms: Date.now() - started,
        error_type: errorType,
    });

    if (!payload || typeof payload.source !== 'string' || !Array.isArray(payload.tests) || payload.tests.length === 0) {
        return finish('error', 0, 0, 'invalid_response');
    }

    const calls = [];
    const context = makeContext(calls);

    try {
        vm.runInContext(payload.source, context, { timeout: execTimeoutMs, displayErrors: false });
    } catch (error) {
        if (error?.code === 'ERR_SCRIPT_EXECUTION_TIMEOUT') {
            return finish('error', 0, 0, 'timeout');
        }
        // Name check, not instanceof: vm errors come from another realm.
        if (error?.name === 'SyntaxError') {
            return finish('failed', payload.tests.length, 0, 'syntax');
        }
        return finish('failed', payload.tests.length, 0, 'runtime');
    }

    let passed = 0;
    let firstError = null;
    for (const test of payload.tests) {
        let outcome = 'assert-fail';
        try {
            if (test?.type === 'function') {
                outcome = await withDeadline(runFunctionTest(context, test.payload, execTimeoutMs), execTimeoutMs, 'timeout');
            } else if (test?.type === 'console') {
                outcome = consoleTestMatches(calls, test.payload) ? 'pass' : 'assert-fail';
            }
        } catch (error) {
            if (String(error?.message ?? '') === 'timeout') {
                return finish('error', payload.tests.length, passed, 'timeout');
            }
            outcome = 'assert-fail';
        }
        if (outcome === 'pass') {
            passed += 1;
        } else if (outcome === 'runtime-error' && firstError === null) {
            firstError = 'runtime';
        }
    }

    if (calls.length >= MAX_OUTPUT_ENTRIES) {
        return finish('failed', payload.tests.length, passed, 'resource_limit');
    }

    if (passed === payload.tests.length) {
        return finish('passed', payload.tests.length, passed, null);
    }
    return finish('failed', payload.tests.length, passed, firstError ?? 'assertion');
}

async function runFunctionTest(context, config, execTimeoutMs) {
    const name = config?.function;
    const cases = config?.cases;
    if (typeof name !== 'string' || name === '' || !Array.isArray(cases) || cases.length === 0) {
        return 'assert-fail';
    }
    // const and let bindings live in the context declarative scope, not as
    // sandbox properties, so resolve the name with an expression lookup.
    let fn;
    try {
        fn = vm.runInContext(`(${name})`, context, { timeout: execTimeoutMs, displayErrors: false });
    } catch {
        return 'assert-fail';
    }
    if (typeof fn !== 'function') {
        return 'assert-fail';
    }
    for (const item of cases) {
        if (!item || !Array.isArray(item.args)) {
            return 'assert-fail';
        }
        // Invoke through the VM so a synchronous hang inside the function
        // body still hits the execution timeout. Arguments serialize as
        // JSON literals, which cannot break out of the call expression.
        // eslint-disable-next-line no-underscore-dangle
        context.__fn = fn;
        let returned;
        try {
            returned = vm.runInContext(`__fn.apply(null, ${JSON.stringify(item.args)})`, context, { timeout: execTimeoutMs, displayErrors: false });
        } catch (error) {
            if (error?.code === 'ERR_SCRIPT_EXECUTION_TIMEOUT') {
                throw new Error('timeout');
            }
            return 'runtime-error';
        }
        let actual;
        try {
            actual = await withDeadline(Promise.resolve(returned), execTimeoutMs, 'timeout');
        } catch (error) {
            if (String(error?.message ?? '') === 'timeout') {
                throw error;
            }
            return 'runtime-error';
        }
        if (!('expected' in item) || !deepEqual(actual, item.expected)) {
            return 'assert-fail';
        }
    }
    return 'pass';
}

function consoleTestMatches(calls, config) {
    const expected = config?.expected;
    if (!Array.isArray(expected) || expected.length === 0) {
        return false;
    }
    if (calls.length !== expected.length) {
        return false;
    }
    for (let index = 0; index < expected.length; index += 1) {
        if (!Array.isArray(expected[index]) || !deepEqual(calls[index], expected[index])) {
            return false;
        }
    }
    return true;
}

async function main() {
    const hardLimitMs = Number.parseInt(process.env.RUNNER_HARD_LIMIT_MS ?? '20000', 10);
    const watchdog = setTimeout(() => {
        process.stdout.write(JSON.stringify({ status: 'error', tests_total: 0, tests_passed: 0, duration_ms: hardLimitMs, error_type: 'timeout' }));
        process.exit(2);
    }, Number.isFinite(hardLimitMs) ? hardLimitMs : 20000);
    watchdog.unref?.();

    let raw = '';
    process.stdin.setEncoding('utf8');
    for await (const chunk of process.stdin) {
        raw += chunk;
        if (raw.length > 4 * 1024 * 1024) {
            break;
        }
    }

    let payload;
    try {
        payload = JSON.parse(raw);
    } catch {
        process.stdout.write(JSON.stringify({ status: 'error', tests_total: 0, tests_passed: 0, duration_ms: 0, error_type: 'invalid_response' }));
        clearTimeout(watchdog);
        return;
    }

    const execTimeoutMs = Number.isFinite(Number(payload.exec_timeout_ms)) ? Math.min(Math.max(Number(payload.exec_timeout_ms), 500), 30000) : 8000;
    const result = await runPayload(payload, execTimeoutMs);
    process.stdout.write(JSON.stringify(result));
    clearTimeout(watchdog);
}

main().catch(() => {
    process.stdout.write(JSON.stringify({ status: 'error', tests_total: 0, tests_passed: 0, duration_ms: 0, error_type: 'runtime' }));
});
