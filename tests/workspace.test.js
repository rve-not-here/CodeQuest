import assert from 'node:assert/strict';
import test from 'node:test';
import { initializeWorkspace } from '../resources/js/workspace.js';

class Element {
    constructor(dataset = {}, attributes = {}) {
        this.dataset = dataset;
        this.attributes = attributes;
        this.events = {};
        this.hidden = false;
        this.focused = false;
    }
    setAttribute(name, value) { this.attributes[name] = value; }
    removeAttribute(name) { delete this.attributes[name]; }
    getAttribute(name) { return this.attributes[name]; }
    addEventListener(name, callback) { this.events[name] = callback; }
    focus() { this.focused = true; }
    setPointerCapture(pointer) { this.pointer = pointer; }
    fire(name, fields = {}) { this.events[name]({ preventDefault() {}, ...fields }); }
}

function fixture(width = 1200, layout = 'panes') {
    const media = { matches: false, addEventListener(name, callback) { this.change = callback; } };
    const events = {};
    globalThis.window = {
        matchMedia: () => media,
        getComputedStyle: () => ({ fontSize: '16px' }),
        addEventListener: (name, callback) => { events[name] = callback; },
    };
    const dimensions = { width };
    const panes = ['brief', 'editor', 'preview'].map((name) => new Element({ workspacePane: name }));
    const buttons = ['brief', 'editor', 'preview'].map((name) => new Element({ paneToggle: name }));
    const separators = ['brief', 'preview'].map((name) => new Element({ workspaceResize: name }, {
        'aria-valuemin': name === 'brief' ? '15' : '20',
        'aria-valuemax': name === 'brief' ? '35' : '40',
    }));
    const reset = new Element();
    const hideBrief = new Element();
    const hideLabel = new Element();
    hideBrief.querySelector = () => hideLabel;
    const widthInput = new Element();
    widthInput.value = '340';
    const style = {};
    const workspace = {
        dataset: { workspaceLayout: layout },
        ownerDocument: { documentElement: {} },
        querySelectorAll: (selector) => selector === '[data-workspace-pane]' ? panes : separators,
        style: { setProperty: (name, value) => { style[name] = value; } },
        getBoundingClientRect: () => dimensions,
    };
    const controls = new Element();
    controls.querySelectorAll = () => buttons;
    controls.querySelector = () => reset;
    const root = new Element();
    root.querySelector = (selector) => ({
        '[data-workspace]': workspace,
        '[data-workspace-controls]': controls,
        '[data-workspace-reset]': reset,
        '[data-workspace-hide-brief]': hideBrief,
        '[data-workspace-width]': widthInput,
    })[selector] ?? null;
    initializeWorkspace(root);
    return { media, panes, buttons, separators, reset, style, dimensions, events, root, controls, hideBrief };
}

test('tabbed Challenge shares Code and Preview while keeping desktop instructions visible', () => {
    const { panes, buttons, controls } = fixture(1366, 'tabs');
    assert.deepEqual(panes.map(pane => pane.hidden), [false, false, true]);
    assert.equal(controls.getAttribute('role'), 'tablist');
    assert.equal(buttons[0].hidden, true);
    buttons[2].fire('click');
    assert.deepEqual(panes.map(pane => pane.hidden), [false, true, false]);
    assert.equal(buttons[2].getAttribute('aria-selected'), 'true');
    buttons[1].fire('click');
    assert.deepEqual(panes.map(pane => pane.hidden), [false, false, true]);
});

test('tabbed Challenge supports keyboard tabs and mobile Instructions without replacing content', () => {
    const { panes, buttons, media } = fixture(1366, 'tabs');
    const editor = panes[1];
    editor.document = 'unsaved 日本語';
    buttons[1].fire('keydown', { key: 'ArrowRight' });
    assert.equal(buttons[2].getAttribute('aria-selected'), 'true');
    buttons[2].fire('keydown', { key: 'Home' });
    assert.equal(buttons[1].getAttribute('aria-selected'), 'true');
    media.matches = true;
    media.change();
    buttons[1].fire('keydown', { key: 'ArrowLeft' });
    assert.deepEqual(panes.map(pane => pane.hidden), [false, true, true]);
    buttons[0].fire('keydown', { key: 'End' });
    assert.deepEqual(panes.map(pane => pane.hidden), [true, true, false]);
    buttons[1].fire('click');
    assert.equal(panes[1], editor);
    assert.equal(editor.document, 'unsaved 日本語');
});

test('tabbed Challenge resets to Code and restores hidden Instructions', () => {
    const { panes, buttons, reset, hideBrief, root } = fixture(1366, 'tabs');
    hideBrief.fire('click');
    assert.equal(panes[0].hidden, true);
    root.fire('cq:workspace-pane', { detail: 'preview' });
    assert.equal(buttons[2].getAttribute('aria-selected'), 'true');
    reset.fire('click');
    assert.deepEqual(panes.map(pane => pane.hidden), [false, false, true]);
    assert.equal(buttons[1].getAttribute('aria-selected'), 'true');
});

test('desktop shows task, code and output and reset restores all three', () => {
    const { panes, buttons, separators, reset } = fixture();
    assert.deepEqual(panes.map((pane) => pane.hidden), [false, false, false]);
    buttons[2].fire('click');
    assert.equal(panes[2].hidden, true);
    reset.fire('click');
    assert.deepEqual(panes.map((pane) => pane.hidden), [false, false, false]);
    assert.equal(separators[1].hidden, false);
});

test('accessible tabs preserve the editor document across pane switches and Run', () => {
    const { panes, buttons, media, controls, root } = fixture();
    const editor = panes[1];
    editor.document = 'unsaved\n日本語';
    media.matches = true;
    media.change();
    assert.equal(controls.getAttribute('role'), 'tablist');
    assert.equal(buttons[1].getAttribute('aria-selected'), 'true');
    assert.equal(buttons[0].getAttribute('tabindex'), '-1');
    buttons[1].fire('keydown', { key: 'ArrowLeft' });
    assert.deepEqual(panes.map((pane) => pane.hidden), [false, true, true]);
    assert.equal(buttons[0].focused, true);
    root.fire('cq:workspace-pane', { detail: 'preview' });
    assert.equal(buttons[2].focused, true);
    assert.deepEqual(panes.map((pane) => pane.hidden), [true, true, false]);
    buttons[2].fire('keydown', { key: 'Home' });
    buttons[0].fire('keydown', { key: 'ArrowRight' });
    assert.equal(panes[1], editor);
    assert.equal(editor.document, 'unsaved\n日本語');
    media.matches = false;
    media.change();
    assert.equal(controls.getAttribute('role'), 'group');
    assert.equal(buttons[1].getAttribute('aria-selected'), undefined);
});

test('resizing preserves a usable editor and never hides it', () => {
    const { panes, separators, style, dimensions, events } = fixture();
    separators[1].fire('pointerdown', { button: 0, pointerId: 1, clientX: 900 });
    separators[1].fire('pointermove', { pointerId: 1, clientX: 0 });
    assert.equal(separators[1].focused, true);
    separators[1].fire('pointercancel');
    separators[0].fire('keydown', { key: 'End' });
    separators[1].fire('keydown', { key: 'End' });
    dimensions.width = 1120;
    events.resize();
    const editorWidth = dimensions.width * (1 - (parseInt(style['--brief-width']) + parseInt(style['--preview-width'])) / 100) - 16;
    assert.ok(editorWidth >= 400);
    assert.deepEqual(panes.map((pane) => pane.hidden), [false, false, false]);
});
