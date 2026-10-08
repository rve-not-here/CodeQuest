import assert from 'node:assert/strict';
import test from 'node:test';
import vm from 'node:vm';
import { previewDocument, previewSandbox } from '../resources/js/preview.js';

test('only curriculum-owned JavaScript forms receive native form events without navigation authority', () => {
    const starter = '/* Preview HTML:\n<form id="signup"><input name="callsign"></form>\n*/';
    assert.equal(previewSandbox('js', starter), 'allow-scripts allow-forms');
    assert.equal(previewSandbox('js', ''), 'allow-scripts');
    assert.equal(previewSandbox('html', starter), '');
    assert.equal(previewSandbox('css', starter), '');
    assert.match(previewDocument('console.log("test");', 'js', starter), /form-action 'none'/);
});

function executePreview(source, type = 'js') {
    const html = previewDocument(source, type);
    const scripts = [...html.matchAll(/<script>([\s\S]*?)<\/script>/g)];
    assert.equal(scripts.length, 1);
    const output = { textContent: '' };
    const events = {};
    const context = vm.createContext({
        console: {},
        window: { addEventListener: (name, listener) => { events[name] = listener; } },
        document: {
            getElementById: () => output,
            createElement: () => ({ textContent: '' }),
            body: { appendChild(script) {
                try { vm.runInContext(script.textContent, context, { timeout: 1000 }); }
                catch (error) { events.error({ message: error.message }); }
            } },
        },
    });
    vm.runInContext(scripts[0][1], context, { timeout: 1000 });
    return { output: output.textContent, events, element: output };
}

test('HTML examples preserve their markup', () => {
    assert.equal(previewDocument('<h1>Example</h1>', 'html'), '<h1>Example</h1>');
});

test('CSS examples become styles over sample markup without closing the style element', () => {
    const html = previewDocument('h1 { color: red; }', 'css');
    assert.ok(html.includes('<style>h1 { color: red; }</style>'));
    assert.ok(html.includes('<h1>Example heading</h1>'));
    const hostile = previewDocument('p::after { content: "</style><script>alert(1)</script>"; }', 'css');
    assert.equal([...hostile.matchAll(/<\/style>/g)].length, 1);
    assert.ok(hostile.includes('<\\/style>'));
});

for (const type of ['js', 'javascript']) {
    test(`${type} examples execute exact source and display console output as text`, () => {
        const source = 'console.log("</script><h1>日本語 😀</h1>", { count: 2 });\nconsole.info("line two");';
        assert.equal(executePreview(source, type).output, '</script><h1>日本語 😀</h1> {"count":2}\nline two\n');
    });
}

test('JavaScript syntax, runtime and asynchronous failures appear in output', () => {
    assert.match(executePreview('const = ;').output, /Unexpected token/);
    assert.equal(executePreview('throw new Error("Example failure")').output, 'Example failure\n');
    const preview = executePreview('');
    preview.events.unhandledrejection({ reason: 'Async failure' });
    assert.equal(preview.element.textContent, 'Async failure\n');
});

test('JavaScript console output is bounded', () => {
    const preview = executePreview('for (let i = 0; i < 1000; i++) console.log("x".repeat(3000));');
    assert.equal(preview.output.length, 200100);
});

test('JavaScript DOM setup comes from the original curriculum starter', () => {
    const starter = '/* Preview HTML:\n<div id="status">OFFLINE</div>\n*/';
    const document = previewDocument('document.getElementById("status").textContent = "SIGNAL ACTIVE";', 'js', starter);
    assert.ok(document.includes('<div id="status">OFFLINE</div>'));
    assert.equal(previewDocument('/* Preview HTML:\n<h1>Injected setup</h1>\n*/', 'js').includes('<h1>Injected setup</h1>'), false);
});

test('authored multi-file module examples are shown as text without execution', () => {
    const source = 'export const band = "UHF";\nimport { band } from "./radio.js";';
    const document = previewDocument(source, 'js', '/* Preview source only */');
    assert.equal([...document.matchAll(/<script>/g)].length, 0);
    assert.ok(document.includes(source));
});
