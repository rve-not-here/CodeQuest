const workspace = document.querySelector('.ws');

function activateTab(tab) {
    const list = tab.closest('[role="tablist"]');

    for (const other of list.querySelectorAll('[role="tab"]')) {
        const isActive = other === tab;
        other.setAttribute('aria-selected', String(isActive));
        other.tabIndex = isActive ? 0 : -1;

        const panelId = other.getAttribute('aria-controls');
        if (panelId && ['output', 'files'].includes(list.dataset.tabs)) {
            document.getElementById(panelId).hidden = !isActive;
        }
    }

    if (list.dataset.tabs === 'mobile') {
        workspace.dataset.tab = tab.dataset.tabValue;
    }
}

function showMobileTab(value) {
    const tab = document.querySelector(`[data-tabs="mobile"] [data-tab-value="${value}"]`);
    if (tab) {
        activateTab(tab);
    }
}

function showOutputTab(id) {
    activateTab(document.getElementById(id));
}

for (const list of document.querySelectorAll('[role="tablist"]')) {
    list.addEventListener('click', (event) => {
        const tab = event.target.closest('[role="tab"]');
        if (tab) {
            activateTab(tab);
        }
    });

    list.addEventListener('keydown', (event) => {
        if (!['ArrowRight', 'ArrowLeft', 'Home', 'End'].includes(event.key)) {
            return;
        }
        const tabs = [...list.querySelectorAll('[role="tab"]')];
        const index = tabs.indexOf(document.activeElement);
        event.preventDefault();
        const next = tabs[event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length];
        next.focus();
        activateTab(next);
    });
}

const mobileLayout = window.matchMedia('(width < 64rem)');
function syncPaneSemantics() {
    for (const tab of document.querySelectorAll('[data-tabs="mobile"] [role="tab"]')) {
        const pane = document.getElementById(tab.getAttribute('aria-controls'));
        if (mobileLayout.matches) {
            pane.setAttribute('role', 'tabpanel');
            pane.setAttribute('aria-labelledby', tab.id);
            pane.tabIndex = 0;
        } else {
            pane.removeAttribute('role');
            pane.removeAttribute('aria-labelledby');
            pane.removeAttribute('tabindex');
        }
    }
}
mobileLayout.addEventListener('change', syncPaneSemantics);
syncPaneSemantics();

for (const toggle of document.querySelectorAll('[data-toggle-pane]')) {
    toggle.addEventListener('click', () => {
        const pane = toggle.dataset.togglePane;
        const collapsed = new Set(workspace.dataset.collapsed.split(' ').filter(Boolean));
        collapsed.has(pane) ? collapsed.delete(pane) : collapsed.add(pane);
        workspace.dataset.collapsed = [...collapsed].join(' ');
        toggle.setAttribute('aria-pressed', String(!collapsed.has(pane)));
    });
}

const paneLimits = {
    left: { min: 20, max: 32 },
    right: { min: 22, max: 34 },
};
for (const splitter of document.querySelectorAll('[data-splitter]')) {
    const side = splitter.dataset.splitter;
    const limits = paneLimits[side];
    function resizePane(percent) {
        const value = Math.max(limits.min, Math.min(limits.max, percent));
        workspace.style.setProperty(`--${side}`, `${value}%`);
        splitter.setAttribute('aria-valuenow', String(Math.round(value)));
        splitter.setAttribute('aria-valuetext', `${Math.round(value)} percent of workspace`);
    }
    splitter.addEventListener('pointerdown', (event) => {
        if (event.button !== 0) {
            return;
        }
        event.preventDefault();
        splitter.focus();
        splitter.setPointerCapture(event.pointerId);
        splitter.dataset.dragging = '';
        workspace.style.userSelect = 'none';
    });
    splitter.addEventListener('pointermove', (event) => {
        if (!splitter.hasPointerCapture(event.pointerId)) {
            return;
        }
        const bounds = workspace.getBoundingClientRect();
        const pixels = side === 'left' ? event.clientX - bounds.left : bounds.right - event.clientX;
        resizePane(pixels / bounds.width * 100);
    });
    splitter.addEventListener('lostpointercapture', () => {
        delete splitter.dataset.dragging;
        workspace.style.userSelect = '';
    });
    splitter.addEventListener('keydown', (event) => {
        const grow = side === 'left' ? 'ArrowRight' : 'ArrowLeft';
        const shrink = side === 'left' ? 'ArrowLeft' : 'ArrowRight';
        if (event.key === grow || event.key === shrink) {
            event.preventDefault();
            resizePane(Number(splitter.getAttribute('aria-valuenow')) + (event.key === grow ? 2 : -2));
        }
    });
}

const runningView = document.querySelector('[data-view="running"]');
const resultsView = document.querySelector('[data-view="results"]');
const runningLabel = document.querySelector('[data-running-label]');
const submitBanner = document.querySelector('[data-submit-banner]');
const lastRun = document.querySelector('[data-last-run]');
const draftStatus = document.querySelector('[data-draft-status]');
const actionButtons = document.querySelectorAll('[data-action="run"], [data-action="submit"]');

let isRunning = false;

function grade({ isSubmission }) {
    if (isRunning || solutionDialog.open) {
        return;
    }
    isRunning = true;
    showOutputTab('ot-tests');
    showMobileTab('results');

    runningLabel.textContent = isSubmission ? 'Previewing submission result' : 'Previewing 5 example checks';
    runningView.hidden = false;
    resultsView.hidden = true;
    actionButtons.forEach((button) => (button.disabled = true));

    window.setTimeout(() => {
        runningView.hidden = true;
        resultsView.hidden = false;
        submitBanner.classList.toggle('hidden', !isSubmission);
        lastRun.textContent = '· static result previewed';
        isRunning = false;
        actionButtons.forEach((button) => (button.disabled = false));
    }, 1100);
}

document.querySelector('[data-action="run"]').addEventListener('click', () => grade({ isSubmission: false }));
document.querySelector('[data-action="submit"]').addEventListener('click', () => grade({ isSubmission: true }));

document.querySelector('[data-action="save"]').addEventListener('click', () => {
    draftStatus.lastChild.textContent = 'SAVE PREVIEW · NOT STORED';
    draftStatus.classList.add('text-accent');
    window.setTimeout(() => draftStatus.classList.remove('text-accent'), 1500);
});

document.querySelector('[data-action="hint"]').addEventListener('click', () => {
    const hint = document.querySelector('[data-hint]');
    showMobileTab('instructions');

    if (workspace.dataset.collapsed.includes('instructions')) {
        document.querySelector('[data-toggle-pane="instructions"]').click();
    }

    hint.open = true;
    hint.scrollIntoView({ block: 'nearest', behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth' });
    hint.querySelector('summary').focus({ preventScroll: true });
});

const solutionDialog = document.querySelector('[data-solution-dialog]');
const editor = document.querySelector('[data-editor]');
const starter = editor.innerHTML;
document.querySelector('[data-reset]').addEventListener('click', () => {
    editor.innerHTML = starter;
    activateTab(document.getElementById('ft-css'));
    editor.focus();
});
document.querySelector('[data-action="solution"]').addEventListener('click', () => {
    solutionDialog.returnValue = '';
    solutionDialog.showModal();
});
solutionDialog.addEventListener('close', () => {
    if (solutionDialog.returnValue !== 'confirm') {
        return;
    }
    editor.innerHTML = starter;
    const line = document.createElement('div');
    line.textContent = '  box-sizing: border-box;';
    editor.children[3].before(line);
    activateTab(document.getElementById('ft-css'));
    showMobileTab('code');
    editor.focus();
});

document.addEventListener('keydown', (event) => {
    if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
        event.preventDefault();
        grade({ isSubmission: event.shiftKey });
    }
});
