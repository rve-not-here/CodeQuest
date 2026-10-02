import vm from 'node:vm';
import { encode } from './values.js';

// This process is adversarial. It emits observations, NEVER academic verdicts.
// Hidden expectations stay in the trusted service outside the container.
async function observe(payload, timeout) {
    const calls = [];
    let overflow = false;
    const record = (...args) => {
        if (calls.length >= 200) { overflow = true; return; }
        calls.push(encode(args));
    };
    const context = vm.createContext({ console: Object.freeze({ log: record, info: record }) });
    const output = { protocol: 2, source_error: null, calls, results: [] };
    try {
        vm.runInContext(payload.source, context, { timeout, displayErrors: false });
    } catch (error) {
        output.source_error = error?.code === 'ERR_SCRIPT_EXECUTION_TIMEOUT' ? 'timeout' : error?.name === 'SyntaxError' ? 'syntax' : 'runtime';
        return output;
    }
    for (const test of payload.tests) {
        const row = { error: null, values: [] };
        output.results.push(row);
        if (test.type === 'console') continue;
        try {
            context.__fn = vm.runInContext(`(${test.function})`, context, { timeout });
            if (typeof context.__fn !== 'function') throw new Error('runtime');
            for (const item of test.cases) {
                const returned = vm.runInContext(`__fn.apply(null, ${JSON.stringify(item.args)})`, context, { timeout, displayErrors: false });
                let timer;
                try {
                    const value = await Promise.race([Promise.resolve(returned), new Promise((_, reject) => { timer = setTimeout(() => reject(new Error('timeout')), timeout); })]);
                    row.values.push(encode(value));
                } finally { clearTimeout(timer); }
            }
        } catch (error) {
            row.error = error?.code === 'ERR_SCRIPT_EXECUTION_TIMEOUT' || error?.message === 'timeout' ? 'timeout' : error?.message === 'resource_limit' ? 'resource_limit' : 'runtime';
        }
    }
    if (overflow) output.source_error = 'resource_limit';
    return output;
}

async function main() {
    const watchdog = setTimeout(() => process.exit(2), 20000);
    watchdog.unref();
    let raw = '';
    process.stdin.setEncoding('utf8');
    for await (const chunk of process.stdin) {
        raw += chunk;
        if (Buffer.byteLength(raw) > 256 * 1024) throw new Error('resource_limit');
    }
    const payload = JSON.parse(raw);
    const timeout = Math.min(Math.max(Number(payload.exec_timeout_ms) || 8000, 500), 30000);
    const output = JSON.stringify(await observe(payload, timeout));
    if (Buffer.byteLength(output) > 256 * 1024) throw new Error('resource_limit');
    process.stdout.write(output);
    clearTimeout(watchdog);
}
main().catch(() => process.exitCode = 2);
