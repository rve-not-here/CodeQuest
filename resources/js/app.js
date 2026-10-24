import { initializeGamification } from './gamification.js';
import { initializeKnowledgeCheck } from './knowledge-check.js';

initializeKnowledgeCheck(document);
initializeGamification(document);

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

// Colour theme: Light or Dark, stored as 'cq-theme' (absent = dark).
// Two named options set the mode; neither flips the other.
const themeLabels = { light: 'Light', dark: 'Dark' };
const themeToggles = [...document.querySelectorAll('[data-theme-toggle]')];
let themeMode = document.documentElement.dataset.themeMode === 'dark' ? 'dark' : 'light';

function nextThemeMode(mode) {
    return mode === 'dark' ? 'light' : 'dark';
}

function applyTheme(mode, { announce = false } = {}) {
    themeMode = mode;
    const dark = mode === 'dark';

    document.documentElement.classList.toggle('dark', dark);
    document.documentElement.dataset.themeMode = mode;
    document.documentElement.style.colorScheme = dark ? 'dark' : 'light';

    themeToggles.forEach((button) => {
        if (button.dataset.themeSet !== undefined) {
            // An explicit setter: the option says what it applies, and the
            // pressed state says what is in force.
            button.setAttribute('aria-pressed', String(button.dataset.themeSet === mode));
            return;
        }

        const next = nextThemeMode(mode);
        button.setAttribute('aria-label', `Switch to ${themeLabels[next].toLowerCase()} mode`);
        button.title = `Switch to ${themeLabels[next].toLowerCase()}`;
    });

    if (announce) {
        const announcer = document.querySelector('[data-theme-announcer]');
        if (announcer) {
            announcer.textContent = `Theme set to ${themeLabels[mode].toLowerCase()}.`;
        }
    }
}

// The account is what carries the choice to every device, so a failed write
// is retried once and then reported instead of vanishing. The device keeps
// the choice either way: its timestamp is newer until the account catches up.
function saveThemeToAccount(mode) {
    const endpoint = themeToggles.find((button) => button.dataset.themeEndpoint)?.dataset.themeEndpoint;
    if (!endpoint) return;

    const save = (attempt) => fetch(endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
        },
        body: JSON.stringify({ theme: mode }),
        keepalive: true,
    }).then((response) => {
        if (!response.ok) throw new Error(`Theme save failed with ${response.status}`);
    }).catch((error) => {
        if (attempt < 2) {
            save(attempt + 1);
            return;
        }
        console.warn('CodeQuest could not save the theme to the account.', error);
    });

    save(1);
}

themeToggles.forEach((button) => {
    button.addEventListener('click', () => {
        // The option names the mode it applies. A control without an explicit
        // mode (legacy markup) still flips, but the shell never renders one.
        const mode = button.dataset.themeSet ?? nextThemeMode(themeMode);

        // The device records when it chose, so the next page can tell this
        // choice apart from an account choice that has not landed yet.
        try {
            localStorage.setItem('cq-theme', mode);
            localStorage.setItem('cq-theme-at', String(Date.now()));
        } catch (e) {}

        if (mode !== themeMode) {
            applyTheme(mode, { announce: true });
        }

        saveThemeToAccount(mode);
    });
});

applyTheme(themeMode);

// The page applied a device choice the account does not hold yet (a legacy
// value, or a choice whose account write is still in flight): carry it over
// so the theme applies on every device.
if (document.documentElement.dataset.themeSync === 'device') {
    saveThemeToAccount(themeMode);
}

// Dialog triggers: logout and any future confirmation surface. Native
// <dialog> supplies the focus trap, Escape handling and top-layer stacking;
// this only opens it and closes it from a backdrop click.
const dialogTriggers = [...document.querySelectorAll('[data-dialog-open]')];
dialogTriggers.forEach((trigger) => {
    trigger.addEventListener('click', (event) => {
        const dialog = document.getElementById(trigger.dataset.dialogOpen ?? '');
        if (!(dialog instanceof HTMLDialogElement)) {
            return;
        }
        // A submit button inside the logout form stays a real no-JS logout;
        // with JS it opens the confirmation instead of submitting immediately.
        event.preventDefault();
        if (!dialog.open) {
            dialog.showModal();
        }
        dialog._returnFocus = trigger;
        const cancel = dialog.querySelector('[data-dialog-cancel]');
        if (cancel instanceof HTMLElement) {
            cancel.focus();
        }
    });
});

function restoreDialogFocus(dialog) {
    const trigger = dialog._returnFocus;
    if (!(trigger instanceof HTMLElement)) {
        return;
    }
    // The account disclosure closes as focus enters the dialog, so the trigger
    // is usually hidden here; return focus to its summary instead.
    window.setTimeout(() => {
        const details = trigger.closest('details');
        if (details instanceof HTMLElement && !details.open) {
            details.querySelector('summary')?.focus();
            return;
        }
        trigger.focus();
    }, 0);
}

document.querySelectorAll('dialog[data-dialog]').forEach((dialog) => {
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
            restoreDialogFocus(dialog);
        }
    });
    dialog.addEventListener('close', () => restoreDialogFocus(dialog));
    dialog.addEventListener('cancel', () => restoreDialogFocus(dialog));
    dialog.querySelectorAll('[data-dialog-cancel]').forEach((button) => {
        button.addEventListener('click', () => restoreDialogFocus(dialog));
    });
});
