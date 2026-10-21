import { initializeKnowledgeCheck } from './knowledge-check.js';

initializeKnowledgeCheck(document);

const editorHost = document.getElementById('editor-host');

const workspaceReady = document.querySelector('[data-workspace]')
    ? import('./workspace.js').then(({ initializeWorkspace }) => initializeWorkspace(document))
    : Promise.resolve();

if (editorHost) {
    let editorLoading = false;
    const loadEditor = () => {
        if (editorLoading) {
            return;
        }
        editorLoading = true;
        import('./editor.js').catch((error) => {
            editorHost.setAttribute('role', 'alert');
            editorHost.textContent = 'Editor could not load. Reload this page to try again.';
            console.error('CodeQuest editor failed to load.', error);
        });
    };

    if (document.querySelector('[data-workspace]')) {
        // The graded workspace needs the editor the moment it opens.
        workspaceReady.then(loadEditor);
    } else if (typeof IntersectionObserver === 'undefined') {
        loadEditor();
    } else {
        // The lesson bench is optional, and the editor bundle is heavy. A reading
        // page must not pay for it: the editor arrives when the bench comes near
        // the viewport, or the moment the student reaches for it.
        const observer = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                observer.disconnect();
                loadEditor();
            }
        }, { rootMargin: '300px 0px' });

        observer.observe(editorHost);
        editorHost.addEventListener('pointerdown', () => {
            observer.disconnect();
            loadEditor();
        }, { once: true });
    }
}

// The bench renders its preview before the editor exists, so the preview
// helpers travel ahead of CodeMirror.
if (document.querySelector('[data-bench]')) {
    import('./preview.js').then((preview) => {
        window.CodeQuest = window.CodeQuest || {};
        window.CodeQuest.previewDocument = preview.previewDocument;
        window.CodeQuest.previewSandbox = preview.previewSandbox;
        window.dispatchEvent(new CustomEvent('cq:preview-ready'));
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

// A course index link may point inside a chapter that starts collapsed; reveal it first.
function revealCourseTarget() {
    const target = location.hash ? document.getElementById(location.hash.slice(1)) : null;
    const chapter = target?.closest('details.atlas-path-course');

    if (chapter && !chapter.open) {
        chapter.open = true;
    }
}

revealCourseTarget();
window.addEventListener('hashchange', revealCourseTarget);

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

// Colour theme: Light ↔ Dark, stored as 'cq-theme' (absent = light).
const themeLabels = { light: 'Light', dark: 'Dark' };
const themeToggles = [...document.querySelectorAll('[data-theme-toggle]')];
let themeMode = document.documentElement.dataset.themeMode === 'dark' ? 'dark' : 'light';

function nextThemeMode(mode) {
    return mode === 'dark' ? 'light' : 'dark';
}

function applyTheme(mode, { announce = false } = {}) {
    themeMode = mode;
    const dark = mode === 'dark';
    const next = nextThemeMode(mode);

    document.documentElement.classList.toggle('dark', dark);
    document.documentElement.dataset.themeMode = mode;
    document.documentElement.style.colorScheme = dark ? 'dark' : 'light';

    themeToggles.forEach((button) => {
        button.setAttribute('aria-label', `Theme: ${themeLabels[mode]}. Switch to ${themeLabels[next].toLowerCase()}.`);
        button.title = `Theme: ${themeLabels[mode]}`;

        if (announce) {
            const announcer = button.parentElement?.querySelector('[data-theme-announcer]');
            if (announcer) {
                announcer.textContent = `Theme set to ${themeLabels[mode].toLowerCase()}.`;
            }
        }
    });
}

themeToggles.forEach((button) => {
    button.addEventListener('click', () => {
        const mode = nextThemeMode(themeMode);

        try {
            localStorage.setItem('cq-theme', mode);
        } catch (e) {}

        applyTheme(mode, { announce: true });

        const endpoint = button.dataset.themeEndpoint;
        if (endpoint) {
            fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                },
                body: JSON.stringify({ theme: mode }),
                keepalive: true,
            }).catch(() => {});
        }
    });
});

applyTheme(themeMode);
