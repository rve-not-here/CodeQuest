/* Static prototype state previews. No assessment records are loaded, stored,
   or changed. Mirrors progress.js so the two views behave identically. */
const states = ['default', 'loading', 'error', 'empty'];
const panels = [...document.querySelectorAll('[data-state-panel]')];
const stateLinks = [...document.querySelectorAll('[data-state-link]')];

function showState(requested, { moveFocus = false } = {}) {
    const state = states.includes(requested) ? requested : 'default';

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
    if (moveFocus) {
        // Focus the page heading rather than the retry button, so a keyboard
        // user lands on the view that just recovered instead of a control
        // that no longer exists in the DOM.
        document.getElementById('assessments-title')?.focus();
        history.replaceState(null, '', location.pathname);
    }
}

showState(new URLSearchParams(location.search).get('state'));
document.querySelector('[data-retry]').addEventListener('click', () => showState('default', { moveFocus: true }));
