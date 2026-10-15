await import('/preview-assets/editor.js');
const CM = window.CodeQuest.CodeMirror;
const editor = new CM.EditorView({doc: '', parent: document.querySelector('#editor-host'), extensions: [CM.basicSetup, CM.html(), CM.oneDark]});
window.focusedPreviewEditor = editor;
const grid = document.querySelector('.focus-grid');
const media = matchMedia('(max-width:1100px)');
const collapsed = new Set();
const tabs = [...document.querySelectorAll('[data-work-tab]')];
const disclosures = [...document.querySelectorAll('[data-shell-disclosure]')];
let mode = 'code';

function render() {
    if (!media.matches && mode === 'task') mode = 'code';
    for (const pane of document.querySelectorAll('[data-pane]')) {
        pane.hidden = pane.dataset.pane === 'task'
            ? (media.matches ? mode !== 'task' : collapsed.has('task'))
            : pane.dataset.pane !== mode;
    }
    for (const tab of tabs) {
        tab.hidden = tab.dataset.workTab === 'task' && !media.matches;
        tab.setAttribute('aria-selected', String(tab.dataset.workTab === mode));
        tab.tabIndex = tab.dataset.workTab === mode ? 0 : -1;
    }
    const instructions = document.querySelector('#task');
    if (media.matches) instructions.setAttribute('role', 'tabpanel');
    else instructions.removeAttribute('role');
    instructions.setAttribute('aria-labelledby', media.matches ? 'tab-task' : 'instruction-title');
    for (const button of document.querySelectorAll('[data-collapse]')) {
        const shown = !collapsed.has(button.dataset.collapse);
        button.setAttribute('aria-expanded', String(shown));
        button.querySelector('[data-collapse-label]').textContent = shown ? 'Hide instructions' : 'Show instructions';
        button.querySelector('[data-instructions-icon]').dataset.icon = shown ? 'panel-left-close' : 'panel-left-open';
    }
    grid.classList.toggle('task-collapsed', collapsed.has('task'));
    editor.requestMeasure();
}

for (const tab of tabs) {
    tab.addEventListener('click', () => { mode = tab.dataset.workTab; render(); });
    tab.addEventListener('keydown', event => {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        const visibleTabs = tabs.filter(item => !item.hidden);
        const current = visibleTabs.indexOf(tab);
        const next = event.key === 'Home' ? 0 : event.key === 'End' ? visibleTabs.length - 1
            : (current + (event.key === 'ArrowRight' ? 1 : -1) + visibleTabs.length) % visibleTabs.length;
        mode = visibleTabs[next].dataset.workTab;
        render();
        visibleTabs[next].focus();
    });
}
document.querySelectorAll('[data-collapse]').forEach(button => button.addEventListener('click', () => {
    const name = button.dataset.collapse;
    if (collapsed.has(name)) collapsed.delete(name); else collapsed.add(name);
    render();
}));
document.querySelectorAll('[data-width]').forEach(input => input.addEventListener('input', () => {
    grid.style.setProperty('--task-size', input.value + 'px');
    editor.requestMeasure();
}));
document.querySelector('[data-reset]').addEventListener('click', () => {
    collapsed.clear();
    mode = 'code';
    grid.style.removeProperty('--task-size');
    for (const input of document.querySelectorAll('[data-width]')) input.value = '340';
    render();
});
media.addEventListener('change', render);
render();

document.querySelector('[data-feedback-toggle]').addEventListener('click', event => {
    const body = document.querySelector('#feedback-body');
    body.hidden = !body.hidden;
    event.currentTarget.setAttribute('aria-expanded', String(!body.hidden));
    event.currentTarget.textContent = body.hidden ? 'Expand' : 'Collapse';
    editor.requestMeasure();
});
const dialog = document.querySelector('#preview-dialog');
let pendingAction = null;
const notice = (title, copy, action = null, label = 'Confirm', consequential = false) => {
    disclosures.forEach(disclosure => { disclosure.open = false; });
    document.querySelector('#dialog-title').textContent = title;
    document.querySelector('[data-dialog-copy]').textContent = copy;
    const confirm = document.querySelector('[data-dialog-confirm]');
    confirm.hidden = action === null;
    confirm.textContent = label;
    confirm.className = consequential ? 'btn btn-primary' : 'btn btn-secondary';
    document.querySelector('[data-dialog-cancel]').textContent = action === null ? 'Close preview notice' : 'Cancel';
    pendingAction = action;
    dialog.returnValue = '';
    dialog.showModal();
};
dialog.addEventListener('close', () => {
    const action = pendingAction;
    pendingAction = null;
    if (dialog.returnValue === 'confirm' && action !== null) action();
});
document.querySelector('[data-submit]').addEventListener('click', () => notice('Submit for evaluation?', 'In CodeQuest, Submit sends your code for academic evaluation. A failed submission costs 10 XP. This prototype demonstrates confirmation only. No code will be submitted or graded.', () => {
    document.querySelector('[data-feedback]').textContent = 'Submission preview confirmed. No result was recorded.';
    document.querySelector('[data-submission-feedback]').hidden = false;
    document.querySelector('#feedback-body').hidden = false;
    const feedbackToggle = document.querySelector('[data-feedback-toggle]');
    feedbackToggle.setAttribute('aria-expanded', 'true');
    feedbackToggle.textContent = 'Collapse';
    mode = 'code';
    render();
    document.querySelector('#workspace-status').textContent = 'Prototype only. No submission was sent.';
}, 'Confirm submission', true));
document.querySelector('[data-hint]').addEventListener('click', () => notice('Hint purchase', 'A hint costs 5 XP in the current fixture. No XP is spent by this preview. Existing authorized hint behavior will remain unchanged.'));
document.querySelector('[data-run]').addEventListener('click', () => notice('Run preview?', 'Run displays your code in the preview pane. It does not grade your work, award XP or record a submission.', () => {
    document.querySelector('#preview').srcdoc = window.CodeQuest.previewDocument(editor.state.doc.toString(), 'html', '');
    document.querySelector('[data-preview-state]').textContent = 'Updated locally';
    document.querySelector('#workspace-status').textContent = 'Preview updated. Your code was not graded.';
    mode = 'output';
    render();
}, 'Run preview'));
document.querySelector('[data-fullscreen]').addEventListener('click', async () => {
    document.querySelector('[data-workspace-options]').open = false;
    if (document.fullscreenElement) await document.exitFullscreen(); else await document.querySelector('#focused-workspace').requestFullscreen();
    document.querySelector('[data-workspace-options] summary').focus();
    editor.requestMeasure();
});
document.addEventListener('fullscreenchange', () => {
    document.querySelector('[data-fullscreen-label]').textContent = document.fullscreenElement ? 'Exit fullscreen' : 'Fullscreen';
    document.querySelector('[data-fullscreen-icon]').dataset.icon = document.fullscreenElement ? 'minimize' : 'maximize';
    editor.requestMeasure();
});
document.querySelectorAll('[data-existing-route]').forEach(control => control.addEventListener('click', event => {
    event.preventDefault();
    notice('Existing CodeQuest navigation', `This workspace-only preview does not replace other pages. The application destination remains ${control.dataset.existingRoute}.`);
}));
document.addEventListener('click', event => disclosures.forEach(disclosure => {
    if (disclosure.open && !disclosure.contains(event.target)) disclosure.open = false;
}));
document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
        const disclosure = disclosures.find(item => item.open);
        if (disclosure) { disclosure.open = false; disclosure.querySelector('summary').focus(); }
    }
});
