/* Static prototype state previews. No achievements are loaded, awarded, or
   stored. Mirrors progress.js, assessments.js, and activity.js. */
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
        document.getElementById('achievements-title')?.focus();
        history.replaceState(null, '', location.pathname);
    }
}

showState(new URLSearchParams(location.search).get('state'));
document.querySelector('[data-retry]').addEventListener('click', () => showState('default', { moveFocus: true }));
