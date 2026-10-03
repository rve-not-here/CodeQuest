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
    const controls = { querySelectorAll: () => buttons, querySelector: () => reset };
    initializeWorkspace({ querySelector: (selector) => selector === '[data-workspace]' ? workspace : controls });
    return { media, panes, buttons, separators, reset, style, dimensions, events };
}

test('keyboard and pointer resizing retain minimum pane widths and reset restores defaults', () => {
    const { separators, reset, style } = fixture();
    separators[0].fire('keydown', { key: 'Home' });
    assert.equal(separators[0].getAttribute('aria-valuenow'), '15');
    separators[0].fire('keydown', { key: 'ArrowLeft' });
    assert.equal(style['--brief-width'], '15%');
    separators[1].fire('pointerdown', { button: 0, pointerId: 1, clientX: 900 });
    separators[1].fire('pointermove', { pointerId: 1, clientX: 0 });
    assert.equal(separators[1].getAttribute('aria-valuenow'), '40');
    assert.equal(separators[1].focused, true);
    separators[1].fire('pointercancel');
    separators[1].fire('pointermove', { pointerId: 1, clientX: 900 });
    assert.equal(style['--preview-width'], '40%');
    reset.fire('click');
    assert.equal(style['--brief-width'], '25%');
    assert.equal(style['--preview-width'], '30%');
});

test('collapse, narrow-screen switching and restore preserve the same editor element', () => {
    const { panes, buttons, media, separators, reset } = fixture();
    const editor = panes[1];
    editor.document = 'unsaved\n日本語';
    buttons[0].fire('click');
    assert.equal(panes[0].hidden, true);
    assert.equal(buttons[0].getAttribute('aria-expanded'), 'false');
    assert.equal(separators[0].hidden, true);
    media.matches = true;
    media.change();
    assert.deepEqual(panes.map((pane) => pane.hidden), [true, false, true]);
    buttons[2].fire('click');
    assert.deepEqual(panes.map((pane) => pane.hidden), [true, true, false]);
    buttons[1].fire('click');
    assert.equal(panes[1], editor);
    assert.equal(editor.document, 'unsaved\n日本語');
    media.matches = false;
    media.change();
    reset.fire('click');
    assert.deepEqual(panes.map((pane) => pane.hidden), [false, false, false]);
});

test('restoring either pane clamps combined widths after resizing a collapsed layout', () => {
    const { panes, buttons, separators, style } = fixture();
    buttons[0].fire('click');
    separators[1].fire('keydown', { key: 'End' });
    buttons[0].fire('click');
    assert.equal(style['--brief-width'], '25%');
    assert.equal(style['--preview-width'], '40%');

    buttons[2].fire('click');
    separators[0].fire('keydown', { key: 'End' });
    buttons[2].fire('click');
    assert.equal(style['--brief-width'], '35%');
    assert.equal(style['--preview-width'], '33%');
    assert.equal(separators[1].getAttribute('aria-valuenow'), '33');
    assert.deepEqual(panes.map((pane) => pane.hidden), [false, false, false]);
});

test('pane widths reserve room for the editor and handles as the desktop viewport shrinks', () => {
    const { buttons, separators, style, dimensions, events } = fixture();
    buttons[0].fire('click');
    separators[1].fire('keydown', { key: 'End' });
    buttons[0].fire('click');
    buttons[2].fire('click');
    separators[0].fire('keydown', { key: 'End' });
    buttons[2].fire('click');

    dimensions.width = 1000;
    events.resize();
    assert.equal(style['--brief-width'], '33%');
    assert.equal(style['--preview-width'], '33%');
    assert.ok((parseInt(style['--brief-width']) + parseInt(style['--preview-width'])) / 100 * 1000 + 320 + 16 <= 1000);
});
