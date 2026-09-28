/*
 * Mission Browser filtering. Client-side only. Filter changes replace the URL query without adding history entries.
 *
 * Reads `?state=` to force a prototype state (default, loading, empty,
 * caught-up, check-available, check-completed) for review. These previews do not
 * represent the current mock student progression.
 * `?q=`, `?state-filter=` and `?topic=` seed the real controls.
 */

const search = document.querySelector('[data-filter-search]');
const groups = [...document.querySelectorAll('[data-filter-group]')];
const resetButtons = [...document.querySelectorAll('[data-filter-reset]')];
const list = document.querySelector('[data-mission-list]');
const sections = [...document.querySelectorAll('[data-section]')];
const count = document.querySelector('[data-result-count]');
const echo = document.querySelector('[data-filter-echo]');
const emptyTitle = document.querySelector('[data-empty-title]');
const emptyCopy = document.querySelector('[data-empty-copy]');
const statePanels = [...document.querySelectorAll('[data-state-panel]')];
const stateLinks = [...document.querySelectorAll('[data-state-link]')];

const missions = [...document.querySelectorAll('[data-mission]')];
const params = new URLSearchParams(location.search);

const filters = {
    q: params.get('q') ?? '',
    state: params.get('state-filter') ?? 'all',
    topic: params.get('topic') ?? 'all',
};

let forced = ['loading', 'empty', 'caught-up', 'check-available', 'check-completed'].includes(params.get('state')) ? params.get('state') : 'default';

const TOTAL = 29;
const normalize = (value) => value.trim().toLowerCase().replace(/\s+/g, ' ');
filters.q = filters.q.trim();
if (!['all', 'completed', 'available', 'in-progress', 'locked'].includes(filters.state)) {
    filters.state = 'all';
}
if (!['all', 'html', 'css', 'javascript'].includes(filters.topic)) {
    filters.topic = 'all';
}

const isCheck = (mission) => mission.dataset.kind === 'check';
const isMission = (mission) => !isCheck(mission);

function visible() {
    return missions.filter((mission) => !mission.hidden);
}
function missionsOnly() {
    return visible().filter(isMission).length;
}
function checksOnly() {
    return visible().filter(isCheck).length;
}

/** Distinguish rendered rows from the course's locked future missions. */
function describeCount() {
    const parts = [`${missionsOnly()} of ${TOTAL} missions shown`];
    const checks = checksOnly();
    if (checks > 0) {
        parts.push(`${checks} knowledge check${checks === 1 ? '' : 's'}`);
    }
    return parts.join(' · ');
}

const LABELS = {
    completed: 'Completed',
    current: 'You are here',
    available: 'Available',
    'in-progress': 'In Progress',
    locked: 'Locked',
};

/** A filter is "all" rather than "current", which is not a filter option. */
function stateMatches(mission, wanted) {
    if (wanted === 'all') {
        return true;
    }
    if (wanted === 'in-progress') {
        return mission.dataset.state === 'in-progress' || mission.dataset.state === 'current';
    }
    return mission.dataset.state === wanted;
}

function matches(mission) {
    if (filters.q && !normalize(mission.dataset.search + ' ' + mission.querySelector('.mission-text').textContent).includes(normalize(filters.q))) {
        return false;
    }
    return stateMatches(mission, filters.state) && (filters.topic === 'all' || mission.dataset.topic === filters.topic);
}

function isActive() {
    return filters.q !== '' || filters.state !== 'all' || filters.topic !== 'all';
}

function describe() {
    const parts = [];
    if (filters.q) {
        parts.push(`matching “${filters.q}”`);
    }
    if (filters.state !== 'all') {
        parts.push(LABELS[filters.state] ?? filters.state);
    }
    if (filters.topic !== 'all') {
        parts.push(filters.topic.toUpperCase());
    }
    return parts.length > 0 ? `Filtered by ${parts.join(', ')}` : '';
}

function apply() {
    const check = document.querySelector('[data-check-preview]');
    const previewState = forced.startsWith('check-') ? forced.slice(6) : 'locked';
    check.dataset.state = previewState;
    const checkAction = check.querySelector('[data-check-action]');
    checkAction.replaceChildren();
    const stateLabel = document.createElement('span');
    stateLabel.className = 'mission-state' + (previewState === 'available' ? ' is-info' : '');
    stateLabel.textContent = LABELS[previewState];
    checkAction.append(stateLabel);
    if (previewState === 'available') {
        // KC 02 has a screen now, so this row links to it. M2.5 still has no
        // workspace and keeps its placeholder dialog below.
        const link = document.createElement('a');
        link.className = 'btn btn-secondary btn-sm';
        link.textContent = 'Start';
        link.href = 'knowledge-check.html';
        link.setAttribute('aria-label', 'Start Knowledge Check 02, Box Model');
        checkAction.append(link);
    }
    check.querySelector('[data-check-note]').textContent = previewState === 'locked'
        ? 'Complete M2.4 to M2.6 to unlock.'
        : 'Opens the Knowledge Check: Box Model screen.';
    list.hidden = false;

    let shown = 0;

    for (const mission of missions) {
        const visible = forced !== 'empty' && matches(mission);
        mission.hidden = !visible;
        if (visible) {
            shown += 1;
        }
    }

    // A section is only hidden when it actually lists missions and every one
    // of them is filtered out. Locked sections hold no rows of their own, so
    // checking "any visible mission" would erase the future-progress preview
    // the browser is supposed to show.
    for (const section of sections) {
        const rows = section.querySelectorAll('[data-mission]');
        section.hidden = rows.length > 0 && section.querySelectorAll('[data-mission]:not([hidden])').length === 0;
    }

    for (const link of list.querySelectorAll('aside a[href^="#s"]')) {
        link.closest('li').hidden = document.querySelector(link.getAttribute('href')).closest('[data-section]').hidden;
    }

    for (const button of resetButtons) {
        button.hidden = !isActive();
    }

    let panel = 'default';
    if (forced === 'loading') {
        panel = 'loading';
    } else if (forced === 'caught-up') {
        panel = 'caught-up';
    } else if (forced === 'empty' || shown === 0) {
        panel = 'empty';
    }

    for (const candidate of statePanels) {
        candidate.hidden = candidate.dataset.statePanel !== panel;
    }
    if (list) {
        list.hidden = panel === 'loading' || panel === 'caught-up';
    }

    // The count lives outside the state panels, so it has to be told when the
    // list is not on screen. Loading and preview states must not announce hidden result totals.
    if (count) {
        count.textContent = panel === 'loading' ? 'Loading missions…'
            : panel === 'empty' ? '0 missions shown'
            : panel === 'caught-up' ? 'Prototype preview: caught up'
            : describeCount();
    }
    if (echo) {
        echo.textContent = panel === 'loading' ? ''
            : forced.startsWith('check-') ? 'Prototype Knowledge Check row-state preview' : describe();
    }

    if (panel === 'empty' && emptyTitle && emptyCopy) {
        emptyTitle.textContent = filters.q
            ? `No missions match “${filters.q}”.`
            : 'No missions match your filters.';
        emptyCopy.textContent = filters.q
            ? 'Try a different search term, or widen the state and topic filters.'
            : 'Nothing in this course matches that state and topic. Try widening one of them.';
    }

    for (const link of stateLinks) {
        const isForced = link.dataset.stateLink === forced;
        if (isForced) {
            link.setAttribute('aria-current', 'true');
        } else {
            link.removeAttribute('aria-current');
        }
    }
}

/** Read the URL back into the controls so a reload or a shared link matches. */
function syncControls() {
    if (search) {
        search.value = filters.q;
    }
    for (const group of groups) {
        const kind = group.dataset.filterGroup;
        for (const button of group.querySelectorAll('[data-filter-value]')) {
            const on = button.dataset.filterValue === filters[kind];
            button.setAttribute('aria-pressed', String(on));
        }
    }
}

/**
 * The filter groups are buttons with aria-pressed, not a tablist: they do not
 * swap panels, so aria-selected would lie about what they do.
 */
for (const group of groups) {
    const kind = group.dataset.filterGroup;

    group.addEventListener('click', (event) => {
        const button = event.target.closest('[data-filter-value]');
        if (!button) {
            return;
        }
        clearTimeout(debounce);
        filters.q = search?.value.trim() ?? '';
        filters[kind] = button.dataset.filterValue;
        forced = 'default';
        updateQuery();
        syncControls();
        apply();
    });
}

let debounce;
search?.addEventListener('input', () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        filters.q = search.value.trim();
        forced = 'default';
        updateQuery();
        apply();
    }, 120);
});

for (const button of resetButtons) {
    button.addEventListener('click', () => {
        clearTimeout(debounce);
        forced = 'default';
        filters.q = '';
        filters.state = 'all';
        filters.topic = 'all';
        updateQuery();
        syncControls();
        apply();
        search?.focus();
    });
}

function updateQuery() {
    const url = new URL(location.href);
    url.searchParams.delete('state');
    for (const [key, value] of [['q', filters.q], ['state-filter', filters.state], ['topic', filters.topic]]) {
        if (value === '' || value === 'all') {
            url.searchParams.delete(key);
        } else {
            url.searchParams.set(key, value);
        }
    }
    history.replaceState(null, '', url);
}

const startDialog = document.querySelector('[data-start-dialog]');
function openStartPreview(title, copy) {
    startDialog.querySelector('h2').textContent = title;
    startDialog.querySelector('p').textContent = copy;
    startDialog.showModal();
}
document.querySelector('[data-start-mission]')?.addEventListener('click', () => openStartPreview(
    'M2.5 · Margin collapse and spacing',
    'Prototype only. This mission is available, but its workspace is not implemented. The challenge screen demonstrates M2.4.',
));

syncControls();
apply();

if (forced !== 'default') {
    console.info(`[missions] forced prototype state: ${forced}`);
}
