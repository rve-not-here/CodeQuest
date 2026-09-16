import { EditorView, basicSetup } from 'codemirror';
import { html } from '@codemirror/lang-html';
import { css } from '@codemirror/lang-css';
import { javascript } from '@codemirror/lang-javascript';
import { oneDark } from '@codemirror/theme-one-dark';

window.CodeQuest = window.CodeQuest || {};
window.CodeQuest.CodeMirror = { EditorView, basicSetup, html, css, javascript, oneDark };

window.dispatchEvent(new CustomEvent('cq:codemirror-ready'));

const sidebar = document.getElementById('cq-sidebar');
const backdrop = document.getElementById('cq-backdrop');

function closeSidebar() {
    sidebar?.classList.add('-translate-x-full');
    backdrop?.classList.add('hidden');
}

document.querySelectorAll('[data-open]').forEach((btn) => {
    btn.addEventListener('click', () => {
        sidebar?.classList.remove('-translate-x-full');
        backdrop?.classList.remove('hidden');
    });
});

document.querySelectorAll('[data-close]').forEach((el) => {
    el.addEventListener('click', closeSidebar);
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeSidebar();
});

document.querySelectorAll('[data-dismiss]').forEach((btn) => {
    btn.addEventListener('click', () => btn.closest('[role="status"]')?.remove());
});
