const workspace = document.querySelector('.ws');

function activateTab(tab) {
    const list = tab.closest('[role="tablist"]');

    for (const other of list.querySelectorAll('[role="tab"]')) {
        const isActive = other === tab;
        other.setAttribute('aria-selected', String(isActive));
        other.tabIndex = isActive ? 0 : -1;

        const panelId = other.getAttribute('aria-controls');
        if (panelId && list.dataset.tabs === 'output') {
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
        if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') {
            return;
        }
        const tabs = [...list.querySelectorAll('[role="tab"]')];
        const index = tabs.indexOf(document.activeElement);
        const next = tabs[(index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length];
        next.focus();
        activateTab(next);
    });
}

for (const toggle of document.querySelectorAll('[data-toggle-pane]')) {
    toggle.addEventListener('click', () => {
        const pane = toggle.dataset.togglePane;
        const collapsed = new Set(workspace.dataset.collapsed.split(' ').filter(Boolean));
        const isVisible = collapsed.has(pane);

        isVisible ? collapsed.delete(pane) : collapsed.add(pane);
        workspace.dataset.collapsed = [...collapsed].join(' ');
        toggle.setAttribute('aria-pressed', String(isVisible));
    });
}

const paneLimits = {
    left: { variable: '--left', min: 18, max: 40 },
    right: { variable: '--right', min: 20, max: 44 },
};

function setPaneWidth(side, rem) {
    const { variable, min, max } = paneLimits[side];
    const clamped = Math.min(max, Math.max(min, rem));
    workspace.style.setProperty(variable, `${clamped}rem`);
    document.querySelector(`[data-splitter="${side}"]`).setAttribute('aria-valuenow', String(Math.round(clamped)));
}

for (const splitter of document.querySelectorAll('[data-splitter]')) {
    const side = splitter.dataset.splitter;
    const rootFontSize = parseFloat(getComputedStyle(document.documentElement).fontSize);

    splitter.addEventListener('pointerdown', (event) => {
        splitter.setPointerCapture(event.pointerId);
        splitter.dataset.dragging = '';
        document.body.style.userSelect = 'none';
    });

    splitter.addEventListener('pointermove', (event) => {
        if (!splitter.hasPointerCapture(event.pointerId)) {
            return;
        }
        const bounds = workspace.getBoundingClientRect();
        const px = side === 'left' ? event.clientX - bounds.left : bounds.right - event.clientX;
        setPaneWidth(side, px / rootFontSize);
    });

    splitter.addEventListener('pointerup', (event) => {
        splitter.releasePointerCapture(event.pointerId);
        delete splitter.dataset.dragging;
        document.body.style.userSelect = '';
    });

    splitter.addEventListener('keydown', (event) => {
        const current = Number(splitter.getAttribute('aria-valuenow'));
        const grow = side === 'left' ? 'ArrowRight' : 'ArrowLeft';
        const shrink = side === 'left' ? 'ArrowLeft' : 'ArrowRight';

        if (event.key === grow || event.key === shrink) {
            event.preventDefault();
            setPaneWidth(side, current + (event.key === grow ? 2 : -2));
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

function timestamp() {
    return new Date().toTimeString().slice(0, 5);
}

function grade({ isSubmission }) {
    showOutputTab('ot-tests');
    showMobileTab('results');

    runningLabel.textContent = isSubmission ? 'Submitting · running 5 graded tests' : 'Running 5 tests';
    runningView.hidden = false;
    resultsView.hidden = true;
    actionButtons.forEach((button) => (button.disabled = true));

    window.setTimeout(() => {
        runningView.hidden = true;
        resultsView.hidden = false;
        submitBanner.classList.toggle('hidden', !isSubmission);
        lastRun.textContent = `· ${isSubmission ? 'submitted' : 'last run'} ${timestamp()}`;
        actionButtons.forEach((button) => (button.disabled = false));
    }, 1100);
}

document.querySelector('[data-action="run"]').addEventListener('click', () => grade({ isSubmission: false }));
document.querySelector('[data-action="submit"]').addEventListener('click', () => grade({ isSubmission: true }));

document.querySelector('[data-action="save"]').addEventListener('click', () => {
    draftStatus.lastChild.textContent = `DRAFT SAVED ${timestamp()}`;
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
    hint.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    hint.querySelector('summary').focus({ preventScroll: true });
});

const solutionDialog = document.querySelector('[data-solution-dialog]');
document.querySelector('[data-action="solution"]').addEventListener('click', () => solutionDialog.showModal());

document.addEventListener('keydown', (event) => {
    if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
        event.preventDefault();
        grade({ isSubmission: event.shiftKey });
    }
});
