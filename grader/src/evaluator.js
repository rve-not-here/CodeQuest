import { isDeepStrictEqual } from 'node:util';
import { encode } from './values.js';

export const PROTOCOL_VERSION = 2;
export const MAX_OUTPUT_BYTES = 256 * 1024;

export function result(status, total, passed, error = null, duration = 0) {
    return { protocol_version: PROTOCOL_VERSION, status, tests_total: total, tests_passed: passed, duration_ms: duration, error_type: error };
}

// Only invocation inputs cross into adversarial execution. Expectations and
// verdict computation remain in the HTTP service, outside that process/container.
export function executionPayload(payload, timeout = 8000) {
    if (!payload || typeof payload.source !== 'string' || !Array.isArray(payload.tests) || payload.tests.length === 0 || payload.tests.length > 50) throw new Error('invalid_response');
    const tests = payload.tests.map(test => {
        if (test.type === 'console' && Array.isArray(test.payload?.expected) && test.payload.expected.length > 0) {
            test.payload.expected.forEach(entry => { if (!Array.isArray(entry)) throw new Error('invalid_response'); encode(entry); });
            return { type: 'console' };
        }
        if (test.type !== 'function' || typeof test.payload?.function !== 'string' || test.payload.function.trim() === '' || test.payload.function.length > 120 || !Array.isArray(test.payload?.cases) || test.payload.cases.length === 0 || test.payload.cases.length > 50) throw new Error('invalid_response');
        return { type: 'function', function: test.payload.function, cases: test.payload.cases.map(item => {
            if (!Array.isArray(item?.args) || !Object.hasOwn(item, 'expected')) throw new Error('invalid_response');
            encode(item.expected);
            return { args: item.args };
        }) };
    });
    return { source: payload.source, tests, exec_timeout_ms: timeout };
}

export function evaluate(payload, raw, duration = 0) {
    const invalid = () => result('error', 0, 0, 'invalid_response', duration);
    try {
        executionPayload(payload);
        if (typeof raw !== 'string' || Buffer.byteLength(raw) > MAX_OUTPUT_BYTES) return invalid();
        const output = JSON.parse(raw);
        if (!output || Object.keys(output).sort().join(',') !== 'calls,protocol,results,source_error' || output.protocol !== PROTOCOL_VERSION || !Array.isArray(output.calls) || !Array.isArray(output.results)) return invalid();
        const total = payload.tests.length;
        if (output.source_error !== null) {
            if (output.source_error === 'timeout') return result('error', total, 0, 'timeout', duration);
            if (['syntax', 'runtime', 'resource_limit'].includes(output.source_error)) return result('failed', total, 0, output.source_error, duration);
            return invalid();
        }
        if (output.results.length !== total) return invalid();
        let passed = 0;
        let errorType = 'assertion';
        for (let index = 0; index < total; index++) {
            const test = payload.tests[index];
            const actual = output.results[index];
            if (!actual || Object.keys(actual).sort().join(',') !== 'error,values' || !Array.isArray(actual.values)) return invalid();
            if (actual.error !== null) {
                if (actual.error === 'timeout') return result('error', total, 0, 'timeout', duration);
                if (!['runtime', 'resource_limit'].includes(actual.error)) return invalid();
                errorType = actual.error;
                continue;
            }
            if (test.type === 'console') {
                if (isDeepStrictEqual(output.calls, test.payload.expected.map(entry => encode(entry)))) passed++;
            } else if (actual.values.length === test.payload.cases.length && actual.values.every((value, n) => isDeepStrictEqual(value, encode(test.payload.cases[n].expected)))) {
                passed++;
            }
        }
        return result(passed === total ? 'passed' : 'failed', total, passed, passed === total ? null : errorType, duration);
    } catch {
        return invalid();
    }
}
