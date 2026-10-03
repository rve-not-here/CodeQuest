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

function fixture(width = 1200) {
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
    const style = {};
    const workspace = {
        ownerDocument: { documentElement: {} },
        querySelectorAll: (selector) => selector === '[data-workspace-pane]' ? panes : separators,
        style: { setProperty: (name, value) => { style[name] = value; } },
        getBoundingClientRect: () => dimensions,
    };
    const controls = new Element();
    controls.querySelectorAll = () => buttons;
    controls.querySelector = () => reset;
    const root = new Element();
    root.querySelector = (selector) => selector === '[data-workspace]' ? workspace : controls;
    initializeWorkspace(root);
    return { media, panes, buttons, separators, reset, style, dimensions, events, root, controls };
}

test('editor owns the desktop workspace and reset collapses output', () => {
    const { panes, buttons, separators, reset, style } = fixture();
    assert.deepEqual(panes.map((pane) => pane.hidden), [false, false, true]);
    assert.equal(style['--brief-width'], '25%');
    assert.equal(style['--preview-width'], '0px');
    buttons[2].fire('click');
    separators[1].fire('pointerdown', { button: 0, pointerId: 1, clientX: 900 });
    separators[1].fire('pointermove', { pointerId: 1, clientX: 0 });
    assert.ok(parseInt(style['--brief-width']) + parseInt(style['--preview-width']) <= 40);
    assert.equal(separators[1].focused, true);
    separators[1].fire('pointercancel');
    reset.fire('click');
    assert.equal(style['--brief-width'], '25%');
    assert.equal(style['--preview-width'], '0px');
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

test('restoring resized panes always reserves at least sixty percent for code', () => {
    const { panes, buttons, separators, style, dimensions, events } = fixture();
    buttons[0].fire('click');
    buttons[2].fire('click');
    separators[1].fire('keydown', { key: 'End' });
    buttons[0].fire('click');
    assert.ok(parseInt(style['--brief-width']) + parseInt(style['--preview-width']) <= 40);
    buttons[2].fire('click');
    separators[0].fire('keydown', { key: 'End' });
    buttons[2].fire('click');
    dimensions.width = 1120;
    events.resize();
    assert.ok(parseInt(style['--brief-width']) + parseInt(style['--preview-width']) <= 40);
    const editorWidth = dimensions.width * (1 - (parseInt(style['--brief-width']) + parseInt(style['--preview-width'])) / 100) - 16;
    assert.ok(editorWidth >= dimensions.width * 0.6);
    assert.deepEqual(panes.map((pane) => pane.hidden), [false, false, false]);
});
