import { EditorView, basicSetup } from 'codemirror';
import { html } from '@codemirror/lang-html';
import { css } from '@codemirror/lang-css';
import { javascript } from '@codemirror/lang-javascript';
import { HighlightStyle, syntaxHighlighting } from '@codemirror/language';
import { tags as t } from '@lezer/highlight';
import { previewDocument, previewSandbox } from './preview.js';

// The coding plane is its own compartment on the sheet: paper in both themes, hairline-ruled,
// legible without a dark surround. Colours are the world's inks — margin red for grammar, pass
// green for strings, stamp violet for literals — so the editor is a specimen of the same set
// rather than a foreign tool dropped into it.
const foundry = [
    EditorView.theme(
        {
            '&': {
                color: 'var(--color-fg)',
                backgroundColor: 'var(--color-surface)',
                fontSize: '15px',
            },
            '.cm-content': { caretColor: 'var(--color-accent)' },
            '.cm-cursor, .cm-dropCursor': { borderLeftColor: 'var(--color-accent)' },
            '&.cm-focused > .cm-scroller > .cm-selectionLayer .cm-selectionBackground, .cm-selectionBackground, .cm-content ::selection':
                { backgroundColor: 'color-mix(in srgb, var(--cq-accent) 24%, transparent)' },
            '.cm-activeLine': { backgroundColor: 'color-mix(in srgb, var(--cq-ink) 5%, transparent)' },
            '.cm-gutters': {
                color: 'var(--color-fg-subtle)',
                backgroundColor: 'var(--color-canvas)',
                border: 'none',
            },
            '.cm-activeLineGutter': {
                color: 'var(--color-accent)',
                backgroundColor: 'color-mix(in srgb, var(--cq-ink) 6%, transparent)',
            },
            '.cm-matchingBracket, &.cm-focused .cm-matchingBracket': {
                backgroundColor: 'color-mix(in srgb, var(--cq-accent) 14%, transparent)',
                outline: '1px solid var(--color-accent)',
                color: 'inherit',
            },
            '.cm-nonmatchingBracket': { color: 'var(--color-danger)' },
        },
        { dark: false },
    ),
    syntaxHighlighting(
        HighlightStyle.define([
            // Markup first: HighlightStyle takes the first match, so tag names and angle
            // brackets must precede keyword or every tag reads as a fault.
            { tag: [t.tagName, t.angleBracket], color: 'var(--color-accent)' },
            { tag: [t.keyword, t.modifier, t.controlKeyword], color: 'var(--color-info)' },
            { tag: [t.string, t.special(t.string), t.attributeValue], color: 'var(--color-success)' },
            { tag: [t.number, t.bool, t.null, t.atom], color: 'var(--color-info)' },
            { tag: [t.function(t.variableName), t.function(t.propertyName)], color: 'var(--color-fg)' },
            { tag: [t.variableName, t.propertyName], color: 'var(--color-fg)' },
            { tag: [t.operator, t.punctuation, t.separator], color: 'var(--color-fg-muted)' },
            { tag: [t.heading], color: 'var(--color-accent)', fontWeight: '700' },
            { tag: [t.definition(t.tagName), t.definition(t.variableName)], color: 'var(--color-fg)' },
            { tag: [t.className, t.typeName], color: 'var(--color-info)' },
            { tag: [t.comment, t.lineComment, t.blockComment], color: 'var(--color-fg-subtle)', fontStyle: 'italic' },
            { tag: [t.invalid], color: 'var(--color-danger)' },
            { tag: [t.link, t.url], color: 'var(--color-accent)', textDecoration: 'underline' },
            { tag: t.strong, fontWeight: '700' },
            { tag: t.emphasis, fontStyle: 'italic' },
        ]),
    ),
];

window.CodeQuest = window.CodeQuest || {};
window.CodeQuest.CodeMirror = {
    EditorView,
    basicSetup: [basicSetup, EditorView.contentAttributes.of({ 'aria-label': 'Code editor' })],
    html,
    css,
    javascript,
    foundry,
};
window.CodeQuest.previewDocument = previewDocument;
window.CodeQuest.previewSandbox = previewSandbox;

window.dispatchEvent(new CustomEvent('cq:codemirror-ready'));
