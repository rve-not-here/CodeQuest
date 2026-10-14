import { EditorView, basicSetup } from 'codemirror';
import { html } from '@codemirror/lang-html';
import { css } from '@codemirror/lang-css';
import { javascript } from '@codemirror/lang-javascript';
import { oneDark } from '@codemirror/theme-one-dark';
import { previewDocument, previewSandbox } from './preview.js';

window.CodeQuest = window.CodeQuest || {};
window.CodeQuest.CodeMirror = {
    EditorView,
    basicSetup: [basicSetup, EditorView.contentAttributes.of({ 'aria-label': 'Code editor' })],
    html,
    css,
    javascript,
    oneDark,
};
window.CodeQuest.previewDocument = previewDocument;
window.CodeQuest.previewSandbox = previewSandbox;

window.dispatchEvent(new CustomEvent('cq:codemirror-ready'));
