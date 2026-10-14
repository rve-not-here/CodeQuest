import { initializeKnowledgeCheck } from './knowledge-check.js';

initializeKnowledgeCheck(document);

const editorHost = document.getElementById('editor-host');

const workspaceReady = document.querySelector('[data-workspace]')
    ? import('./workspace.js').then(({ initializeWorkspace }) => initializeWorkspace(document))
    : Promise.resolve();

if (editorHost) {
    workspaceReady.then(() => import('./editor.js')).catch((error) => {
        editorHost.setAttribute('role', 'alert');
        editorHost.textContent = 'Editor could not load. Reload this page to try again.';
        console.error('CodeQuest editor failed to load.', error);
    });
}

document.querySelectorAll('[data-dismiss]').forEach((button) => {
    button.addEventListener('click', () => button.closest('[data-status-message]')?.remove());
});

const disclosures = [...document.querySelectorAll('[data-shell-disclosure]')];

function closeDisclosure(disclosure, restoreFocus = false) {
    disclosure.open = false;
    if (restoreFocus) {
        disclosure.querySelector('summary')?.focus();
    }
}

disclosures.forEach((disclosure) => {
    disclosure.addEventListener('toggle', () => {
        if (disclosure.open) {
            disclosures.filter((other) => other !== disclosure).forEach((other) => closeDisclosure(other));
        }
    });
    disclosure.addEventListener('focusout', (event) => {
        if (event.relatedTarget && !disclosure.contains(event.relatedTarget)) {
            closeDisclosure(disclosure);
        }
    });
});

document.addEventListener('click', (event) => {
    disclosures.filter((disclosure) => disclosure.open && !disclosure.contains(event.target))
        .forEach((disclosure) => closeDisclosure(disclosure));
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        const openDisclosure = disclosures.find((disclosure) => disclosure.open);
        if (openDisclosure) {
            event.preventDefault();
            closeDisclosure(openDisclosure, true);
        }
    }
});

window.matchMedia('(min-width: 1200px)').addEventListener('change', () => {
    disclosures.forEach((disclosure) => {
        if (disclosure.open && !disclosure.getClientRects().length) {
            const hadFocus = disclosure.contains(document.activeElement);
            closeDisclosure(disclosure);
            if (hadFocus) {
                document.querySelector('.atlas-brand')?.focus();
            }
        }
    });
});
