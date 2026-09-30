import { EditorView, basicSetup } from 'codemirror';
import { html } from '@codemirror/lang-html';
import { css } from '@codemirror/lang-css';
import { javascript } from '@codemirror/lang-javascript';
import { oneDark } from '@codemirror/theme-one-dark';

window.CodeQuest = window.CodeQuest || {};
window.CodeQuest.CodeMirror = { EditorView, basicSetup, html, css, javascript, oneDark };

window.dispatchEvent(new CustomEvent('cq:codemirror-ready'));
