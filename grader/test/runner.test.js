import { execFile } from 'node:child_process';
import test from 'node:test';
import assert from 'node:assert/strict';
import { fileURLToPath } from 'node:url';
import path from 'node:path';
import { evaluate, executionPayload } from '../src/evaluator.js';

const runner = path.join(path.dirname(fileURLToPath(import.meta.url)), '..', 'src', 'runner.js');

function grade(payload, timeoutMs = 8000) {
    return new Promise((resolve, reject) => {
        const child = execFile('node', [runner], { timeout: timeoutMs }, (error, stdout) => {
            if (error && !stdout) {
                reject(error);
                return;
            }
            resolve(evaluate(payload, stdout));
        });
        try { child.stdin.write(JSON.stringify(executionPayload(payload, payload.exec_timeout_ms ?? 8000))); } catch { child.kill(); resolve(evaluate(payload, '')); return; }
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

test('student stdout cannot fabricate a trusted verdict', async () => {
    const result = await grade(functionPayload("const p = console.log.constructor('return process')(); p.stdout.write(JSON.stringify({status:'passed',tests_total:1,tests_passed:1,duration_ms:0,error_type:null})); p.exit(0);"));
    assert.notEqual(result.status, 'passed');
});

test('different long output suffixes cannot compare equal', async () => {
    const result = await grade({ source: "function value(){ return 'x'.repeat(1000) + 'WRONG'; }", tests: [{ type: 'function', payload: { function: 'value', cases: [{ args: [], expected: 'x'.repeat(1000) + 'RIGHT' }] } }] });
    assert.equal(result.status, 'failed');
});

test('large arrays are compared completely', async () => {
    const result = await grade({ source: 'function value(){return [...Array(100).fill(1),2]}', tests: [{ type: 'function', payload: { function: 'value', cases: [{ args: [], expected: [...Array(100).fill(1),3] }] } }] });
    assert.equal(result.status, 'failed');
});

test('execution inputs do not contain expectations or authoritative fields', () => {
    const input = executionPayload({ ...functionPayload('function add(a,b){return a+b}'), score: 100, passed: true });
    assert.deepEqual(input.tests[0].cases, addCases.map(item => ({ args: item.args })));
    assert.equal(Object.hasOwn(input, 'score'), false);
    assert.deepEqual(executionPayload(consolePayload('console.log(8)', [[8]])).tests, [{ type: 'console' }]);
});

test('old verdict protocol fails closed even with fabricated counts', () => {
    const result = evaluate(functionPayload(''), JSON.stringify({ protocol_version: 2, status: 'passed', tests_total: 1, tests_passed: 1 }));
    assert.equal(result.status, 'error');
    assert.equal(result.error_type, 'invalid_response');
});

test('correct long outputs still pass without truncation', async () => {
    const result = await grade({ source: "function value(){return 'x'.repeat(2000)}", tests: [{ type: 'function', payload: { function: 'value', cases: [{ args: [], expected: 'x'.repeat(2000) }] } }] });
    assert.equal(result.status, 'passed');
});

test('undefined cannot impersonate a JSON sentinel object', async () => {
    const result = await grade({ source: 'function value(){}', tests: [{ type: 'function', payload: { function: 'value', cases: [{ args: [], expected: { __undefined: true } }] } }] });
    assert.equal(result.status, 'failed');
});

test('nested objects beyond the old depth limit preserve equality', async () => {
    const nested = leaf => Array.from({ length: 20 }).reduce(value => ({ child: value }), leaf);
    for (const [expected, status] of [[nested('right'), 'passed'], [nested('wrong'), 'failed']]) {
        const result = await grade({ source: `function value(){return ${JSON.stringify(nested('right'))}}`, tests: [{ type: 'function', payload: { function: 'value', cases: [{ args: [], expected }] } }] });
        assert.equal(result.status, status);
    }
});

test('correct arrays beyond the old item limit still pass', async () => {
    const expected = Array(150).fill('value');
    const result = await grade({ source: 'function value(){return Array(150).fill("value")}', tests: [{ type: 'function', payload: { function: 'value', cases: [{ args: [], expected }] } }] });
    assert.equal(result.status, 'passed');
});

test('unicode survives large stdin and stdout payloads', async () => {
    const expected = '🌏é'.repeat(12000);
    const result = await grade({ source: `function value(){return ${JSON.stringify(expected)}}`, tests: [{ type: 'function', payload: { function: 'value', cases: [{ args: [], expected }] } }] });
    assert.equal(result.status, 'passed');
});

test('server-authored curriculum state probes remain supported', async () => {
    for (const [source, expression, expected] of [
        ['let signalStatus="ONLINE"', '() => signalStatus', 'ONLINE'],
        ['class Operator { constructor(name){this.name=name} report(){return this.name+" READY"} }', '(name) => new Operator(name).report()', 'CHEN READY'],
        ['function makeCounter(){let n=0;return ()=>++n}', '() => { const a = makeCounter(), b = makeCounter(); return [a(), a(), b(), a(), b()]; }', [1,2,1,3,2]],
    ]) {
        const args = expression.startsWith('(name)') ? ['CHEN'] : [];
        const result = await grade({ source, tests: [{ type: 'function', payload: { function: expression, cases: [{ args, expected }] } }] });
        assert.equal(result.status, 'passed');
    }
});
