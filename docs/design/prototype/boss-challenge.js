/* Static prototype state previews for the Boss Challenge.
 *
 * The default state is `locked`, which is the canonical student's real
 * position. Every other value is a preview for inspection and is labelled as
 * such on the page. Nothing here submits, grades, stores, or awards anything.
 */
const states = ['locked', 'available', 'in-progress', 'submitted', 'passed', 'failed', 'loading', 'error'];
const panels = [...document.querySelectorAll('[data-state-panel]')];
const stateLinks = [...document.querySelectorAll('[data-state-link]')];
const previewNote = document.querySelector('[data-preview-note]');
const previewNoteText = document.querySelector('[data-preview-note-text]');
const statusLabel = document.querySelector('[data-boss-status]');
const statusNote = document.querySelector('[data-boss-note]');
const statusBadge = document.querySelector('[data-boss-badge]');
const workspace = document.querySelector('[data-boss-ws]');

/* Status text, the badge tone, and the plain-language note beside it. The
   label is never carried by the badge alone. */
const statusCopy = {
    locked: ['Locked', 'badge-locked text-fg-muted!', 'Not yet available'],
    available: ['Available', 'badge-accent', 'Ready to start'],
    'in-progress': ['In Progress', 'badge-info', 'Draft in progress'],
    submitted: ['Submitted', 'badge-info', 'Evaluation pending'],
    passed: ['Passed', 'badge-accent', 'Challenge complete'],
    failed: ['Needs another attempt', 'badge-warning', 'Draft kept'],
    loading: ['Loading', 'badge-info', 'Fetching requirements'],
    error: ['Unavailable', 'badge-danger', "Couldn't load"],
};

const previewDescriptions = {
    available: 'This state is illustrative and is not the current student’s saved assessment state.',
    'in-progress': 'This workspace is illustrative. The editor does not run code and no attempt is recorded.',
    submitted: 'No submission is sent and no evaluation is queued in this prototype.',
    passed: 'This result is illustrative. It does not recompute competency, award XP, or record an attempt.',
    failed: 'This outcome is illustrative. No XP is deducted and no attempt is counted.',
    loading: 'This state shows loading behaviour only.',
    error: 'This state shows error behaviour only.',
};

const ICON = {
    locked: 'i-lock',
    available: 'i-play',
    'in-progress': 'i-play',
    submitted: 'i-inbox',
    passed: 'i-award',
    failed: 'i-alert',
    loading: 'i-loader',
    error: 'i-alert',
};

function setStatus(state) {
    const copy = statusCopy[state];
    if (!copy) {
        return;
    }
    const [label, tone, note] = copy;

    statusLabel.textContent = label;
    statusNote.textContent = note;

    statusBadge.hidden = false;
    statusBadge.className = `badge ${tone}`;
    statusBadge.querySelector('use').setAttribute('href', `#${ICON[state]}`);
}

function showState(requested, { moveFocus = false } = {}) {
    const state = states.includes(requested) ? requested : 'locked';

    for (const panel of panels) {
        panel.hidden = panel.dataset.statePanel !== state;
    }
    for (const link of stateLinks) {
        if (link.dataset.stateLink === state) {
            link.setAttribute('aria-current', 'true');
        } else {
            link.removeAttribute('aria-current');
        }
    }

    setStatus(state);

    const isPreview = previewDescriptions[state];
    previewNote.hidden = !isPreview;
    if (isPreview) {
        previewNoteText.textContent = previewDescriptions[state];
    }

    if (moveFocus) {
        const target = state === 'submitted' ? 'submitted-title' : 'boss-title';
        document.getElementById(target)?.focus();
        const url = new URL(location.href);
        state === 'locked' ? url.searchParams.delete('state') : url.searchParams.set('state', state);
        history.replaceState(null, '', url);
    }
}

showState(new URLSearchParams(location.search).get('state'));
document.querySelector('[data-retry]').addEventListener('click', () => showState('locked', { moveFocus: true }));

/* ── Moving between preview states ─────────────────────────────────────
   The available state offers a Start button, and the result states offer a
   way back to the workspace. Both are navigation between previews, so they
   rewrite the query rather than opening a dialog. */
for (const enter of document.querySelectorAll('[data-enter-workspace]')) {
    enter.addEventListener('click', () => showState('in-progress', { moveFocus: true }));
}
for (const reopen of document.querySelectorAll('[data-reopen-workspace]')) {
    reopen.addEventListener('click', () => showState('in-progress', { moveFocus: true }));
}

/* ── Workspace tabs ───────────────────────────────────────────────────
   Same behaviour as the Challenge Workspace: arrow keys move between tabs,
   and the mobile tablist drives which pane is visible. */
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

    if (list.dataset.tabs === 'files') {
        document.querySelector('[data-file-language]').textContent = tab.id === 'bf-js' ? 'JavaScript' : 'CSS';
    }

    if (list.dataset.tabs === 'mobile' && workspace) {
        workspace.dataset.tab = tab.dataset.tabValue;
    }
}

function showMobileTab(value) {
    const tab = document.querySelector(`[data-tabs="mobile"] [data-tab-value="${value}"]`);
    if (tab) {
        activateTab(tab);
    }
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
        event.preventDefault();
        const tabs = [...list.querySelectorAll('[role="tab"]')];
        const index = tabs.indexOf(document.activeElement);
        const next = tabs[(index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length];
        next.focus();
        activateTab(next);
    });
}

/* ── Run, save, submit ──────────────────────────────────────────────── */
const runningView = document.querySelector('[data-view="running"]');
const resultsView = document.querySelector('[data-view="results"]');
const runningLabel = document.querySelector('[data-running-label]');
const lastRun = document.querySelector('[data-last-run]');
const draftStatus = document.querySelector('[data-draft-status]');
const actionButtons = document.querySelectorAll('[data-action="run"], [data-action="submit"]');

function timestamp() {
    return new Date().toTimeString().slice(0, 5);
}

let isRunning = false;

function runTests() {
    if (isRunning || workspace.closest('[data-state-panel]').hidden || submitDialog.open) {
        return;
    }
    isRunning = true;
    document.getElementById('bot-tests')?.click();
    showMobileTab('results');
    document.getElementById('bot-tests').focus();

    runningLabel.textContent = 'Previewing 9 example checks';
    runningView.hidden = false;
    resultsView.hidden = true;
    for (const button of actionButtons) {
        button.disabled = true;
    }

    window.setTimeout(() => {
        runningView.hidden = true;
        resultsView.hidden = false;
        lastRun.textContent = `· static results previewed ${timestamp()}`;
        for (const button of actionButtons) {
            button.disabled = false;
        }
        isRunning = false;
    }, 1100);
}

document.querySelector('[data-action="run"]')?.addEventListener('click', runTests);
document.querySelector('[data-action="save"]')?.addEventListener('click', () => {
    draftStatus.lastChild.textContent = `SAVE PREVIEW ${timestamp()} · NOT STORED`;
});

/* Submitting is one deliberate action, so it asks first. A native dialog
   handles Escape and focus return; the close event decides where to go. */
const submitDialog = document.querySelector('[data-submit-dialog]');

document.querySelector('[data-action="submit"]')?.addEventListener('click', () => {
    submitDialog.returnValue = '';
    submitDialog.showModal();
});

submitDialog.addEventListener('close', () => {
    if (submitDialog.returnValue === 'confirm') {
        showState('submitted', { moveFocus: true });
    }
});

document.addEventListener('keydown', (event) => {
    if ((event.ctrlKey || event.metaKey) && event.key === 'Enter' && !workspace.closest('[data-state-panel]').hidden && !submitDialog.open) {
        event.preventDefault();
        runTests();
    }
});

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
