/*
 * Competency — prototype only.
 *
 * One job: swap which of the six committed detail panels is visible, and keep
 * the URL in step. All six panels already exist in the markup, so there is no
 * copy of the competency data in this file to drift out of sync with the list.
 *
 * Nothing is stored. Reloading restores the competency selected in the URL.
 */

const STATES = ['default', 'loading', 'empty', 'error'];
// The query value is `default`, but the panel that shows it is the profile
// panel. Keeping the two apart stops the state switch from hiding the page.
const STATE_PANELS = {
    default: 'profile',
    loading: 'loading',
    empty: 'empty',
    error: 'error',
};
const DEFAULT_COMPETENCY = 'box-model';

const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector) => [...document.querySelectorAll(selector)];

const params = new URLSearchParams(location.search);
const requestedState = params.get('state');
const requestedCompetency = params.get('competency');

const panels = $$('[data-state-panel]');
const rows = $$('[data-competency]');
const details = $$('[data-competency-detail]');
const stateLinks = $$('[data-state-link]');

const knownCompetencies = rows.map((row) => row.dataset.competency);
// Anything unrecognised falls back rather than rendering nothing.
const state = STATES.includes(requestedState) ? requestedState : 'default';
const initialCompetency = knownCompetencies.includes(requestedCompetency) ? requestedCompetency : DEFAULT_COMPETENCY;

function selectCompetency(key, { moveFocus = true, push = true } = {}) {
    if (!knownCompetencies.includes(key)) {
        return;
    }

    for (const detail of details) {
        detail.hidden = detail.dataset.competencyDetail !== key;
    }
    for (const row of rows) {
        // aria-current="true" is truthful here: the row is the current item
        // in this page's own list-to-detail navigation.
        if (row.dataset.competency === key) {
            row.setAttribute('aria-current', 'true');
        } else {
            row.removeAttribute('aria-current');
        }
    }

    if (moveFocus) {
        // Focus follows the selection so keyboard and screen-reader users land
        // on the new detail, but without throwing focus to the top of the page.
        const heading = $(`#detail-title-${CSS.escape(key)}`);
        heading?.focus({ preventScroll: true });
        heading?.scrollIntoView({ block: 'nearest' });
    }

    if (push) {
        const url = key === DEFAULT_COMPETENCY
            ? location.pathname
            : `${location.pathname}?competency=${key}`;
        // replaceState, not pushState: moving along a list should not fill
        // the back button with selections.
        history.replaceState(null, '', url);
    }
}

function setState(next, { push = true } = {}) {
    next = STATES.includes(next) ? next : 'default';
    const panelName = STATE_PANELS[next];
    $('[data-profile-context]').hidden = next !== 'default';

    for (const panel of panels) {
        panel.hidden = panel.dataset.statePanel !== panelName;
    }

    for (const link of stateLinks) {
        if (link.dataset.stateLink === next) {
            link.setAttribute('aria-current', 'true');
        } else {
            link.removeAttribute('aria-current');
        }
    }

    if (next === 'default') {
        selectCompetency(initialCompetency, { moveFocus: push, push });
    }

    if (push && next !== 'default') {
        history.replaceState(null, '', `${location.pathname}?state=${next}`);
    }
}

for (const row of rows) {
    row.addEventListener('click', () => selectCompetency(row.dataset.competency));
}

for (const control of $$('[data-state-goto]')) {
    control.addEventListener('click', () => setState(control.dataset.stateGoto));
}

// Boot: no focus movement, so the first paint never paints a focus ring
// around a heading the student did not ask for.
setState(state, { push: false });
