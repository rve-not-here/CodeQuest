import { execFile } from 'node:child_process';
import test from 'node:test';
import assert from 'node:assert/strict';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const runner = path.join(path.dirname(fileURLToPath(import.meta.url)), '..', 'src', 'runner.js');

function grade(payload, timeoutMs = 8000) {
    return new Promise((resolve, reject) => {
        const child = execFile('node', [runner], { timeout: timeoutMs }, (error, stdout) => {
            if (error && !stdout) {
                reject(error);
                return;
            }
            resolve(JSON.parse(stdout));
        });
        child.stdin.write(JSON.stringify(payload));
        child.stdin.end();
    });
}

const addCases = [
    { args: [2, 3], expected: 5 },
    { args: [10, 20], expected: 30 },
    { args: [-5, 5], expected: 0 },
    { args: [0, 0], expected: 0 },
];

function functionPayload(source) {
    return { source, tests: [{ type: 'function', payload: { function: 'add', cases: addCases } }] };
}

test('function declaration passes every hidden case', async () => {
    const result = await grade(functionPayload('function add(a, b) { return a + b; }'));
    assert.equal(result.status, 'passed');
    assert.equal(result.tests_passed, 1);
});

test('arrow function passes', async () => {
    const result = await grade(functionPayload('const add = (a, b) => a + b;'));
    assert.equal(result.status, 'passed');
});

test('function expression passes', async () => {
    const result = await grade(functionPayload('const add = function (a, b) { return a + b; };'));
    assert.equal(result.status, 'passed');
});

test('hard-coded constant fails hidden cases', async () => {
    const result = await grade(functionPayload('function add() { return 8; }'));
    assert.equal(result.status, 'failed');
    assert.equal(result.error_type, 'assertion');
});

test('wrong logic fails', async () => {
    const result = await grade(functionPayload('function add(a, b) { return a - b; }'));
    assert.equal(result.status, 'failed');
});

test('number and string stay distinct', async () => {
    const result = await grade({
        source: 'function add(a, b) { return String(a + b); }',
        tests: [{ type: 'function', payload: { function: 'add', cases: [{ args: [2, 3], expected: 5 }] } }],
    });
    assert.equal(result.status, 'failed');
});

test('arrays and objects compare by value', async () => {
    const result = await grade({
        source: 'function pair(a, b) { return { sum: a + b, items: [a, b] }; }',
        tests: [{ type: 'function', payload: { function: 'pair', cases: [{ args: [2, 3], expected: { sum: 5, items: [2, 3] } }] } }],
    });
    assert.equal(result.status, 'passed');
});

test('async function results resolve', async () => {
    const result = await grade({
        source: 'async function value() { return 42; }',
        tests: [{ type: 'function', payload: { function: 'value', cases: [{ args: [], expected: 42 }] } }],
    });
    assert.equal(result.status, 'passed');
});

function consolePayload(source, expected) {
    return { source, tests: [{ type: 'console', payload: { expected } }] };
}

test('literal console output passes', async () => {
    const result = await grade(consolePayload('console.log(8);', [[8]]));
    assert.equal(result.status, 'passed');
});

test('variable console output passes', async () => {
    const result = await grade(consolePayload('const result = 5 + 3;\nconsole.log(result);', [[8]]));
    assert.equal(result.status, 'passed');
});

test('function-call console output passes', async () => {
    const result = await grade(consolePayload('function add(a, b) { return a + b; }\nconsole.log(add(5, 3));', [[8]]));
    assert.equal(result.status, 'passed');
});

test('console.info counts as output', async () => {
    const result = await grade(consolePayload('const result = 5 + 3;\nconsole.info(result);', [[8]]));
    assert.equal(result.status, 'passed');
});

test('multi-argument calls stay distinguishable', async () => {
    const result = await grade(consolePayload('console.log("Score:", 10);', [['Score:', 10]]));
    assert.equal(result.status, 'passed');
});

test('commented-out console call fails', async () => {
    const result = await grade(consolePayload('function add(a, b) { return a + b; }\n// console.log(add(5, 3));', [[8]]));
    assert.equal(result.status, 'failed');
});

test('wrong console output fails', async () => {
    const result = await grade(consolePayload('console.log(9);', [[8]]));
    assert.equal(result.status, 'failed');
});

test('syntax error reports syntax', async () => {
    const result = await grade(functionPayload('function add(a, b) { return '));
    assert.equal(result.status, 'failed');
    assert.equal(result.error_type, 'syntax');
});

test('thrown exception reports runtime', async () => {
    const result = await grade(functionPayload('function add(a, b) { throw new Error("boom"); }'));
    assert.equal(result.status, 'failed');
    assert.equal(result.error_type, 'runtime');
});

test('infinite loop is terminated as a timeout', async () => {
    const result = await grade({
        source: 'while (true) {}',
        exec_timeout_ms: 1000,
        tests: [{ type: 'console', payload: { expected: [[1]] } }],
    });
    assert.equal(result.status, 'error');
    assert.equal(result.error_type, 'timeout');
});

test('infinite loop inside a function body is terminated as a timeout', async () => {
    const result = await grade({
        source: 'function add(a, b) { while (true) {} }',
        exec_timeout_ms: 1000,
        tests: [{ type: 'function', payload: { function: 'add', cases: [{ args: [2, 3], expected: 5 }] } }],
    });
    assert.equal(result.status, 'error');
    assert.equal(result.error_type, 'timeout');
});

test('excessive output is bounded, not fatal to the harness', async () => {
    const result = await grade({
        source: 'for (let i = 0; i < 5000; i++) { console.log("line " + i); }',
        tests: [{ type: 'console', payload: { expected: [[8]] } }],
    });
    assert.equal(result.status, 'failed');
    assert.equal(result.error_type, 'resource_limit');
});

test('missing function fails instead of crashing', async () => {
    const result = await grade(functionPayload('const x = 1;'));
    assert.equal(result.status, 'failed');
});

test('malformed payload is rejected', async () => {
    const result = await grade({ nonsense: true });
    assert.equal(result.status, 'error');
    assert.equal(result.error_type, 'invalid_response');
});
