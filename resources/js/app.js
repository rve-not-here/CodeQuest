const editorHost = document.getElementById('editor-host');

if (document.querySelector('[data-workspace]')) {
    import('./workspace.js').then(({ initializeWorkspace }) => initializeWorkspace(document));
}

if (editorHost) {
    import('./editor.js').catch((error) => {
        editorHost.setAttribute('role', 'alert');
        editorHost.textContent = 'Editor could not load. Reload this page to try again.';
        console.error('CodeQuest editor failed to load.', error);
    });
}

const sidebar = document.getElementById('cq-sidebar');
const backdrop = document.getElementById('cq-backdrop');
const sidebarTrigger = document.querySelector('[data-open]');
let sidebarReturnFocus = null;

function sidebarFocusableElements() {
    return [...(sidebar?.querySelectorAll(
        'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
    ) ?? [])];
}

function setDrawerBackgroundInert(isInert) {
    document.querySelectorAll('[data-drawer-content]').forEach((element) => {
        if (isInert) {
            element.setAttribute('inert', '');
        } else {
            element.removeAttribute('inert');
        }
    });
}

function openSidebar(opener) {
    if (!sidebar || !backdrop || !sidebarTrigger) {
        return;
    }

    sidebarReturnFocus = opener;
    sidebar.removeAttribute('inert');
    sidebar.setAttribute('aria-hidden', 'false');
    sidebar.classList.remove('-translate-x-full');
    backdrop.classList.remove('hidden');
    sidebarTrigger.setAttribute('aria-expanded', 'true');
    setDrawerBackgroundInert(true);
    document.documentElement.classList.add('drawer-open');

    window.requestAnimationFrame(() => {
        (sidebar.querySelector('[data-drawer-initial-focus]') ?? sidebar).focus();
    });
}

function closeSidebar(restoreFocus = true) {
    if (!sidebar || !backdrop || !sidebarTrigger) {
        return;
    }

    sidebar.classList.add('-translate-x-full');
    sidebar.setAttribute('aria-hidden', 'true');
    sidebar.setAttribute('inert', '');
    backdrop.classList.add('hidden');
    sidebarTrigger.setAttribute('aria-expanded', 'false');
    setDrawerBackgroundInert(false);
    document.documentElement.classList.remove('drawer-open');

    if (restoreFocus && sidebarReturnFocus instanceof HTMLElement) {
        sidebarReturnFocus.focus();
    }
}

sidebarTrigger?.addEventListener('click', () => openSidebar(sidebarTrigger));

document.querySelectorAll('[data-close]').forEach((el) => {
    el.addEventListener('click', closeSidebar);
});

document.addEventListener('keydown', (e) => {
    if (sidebar?.getAttribute('aria-hidden') !== 'false') {
        return;
    }

    if (e.key === 'Escape') {
        e.preventDefault();
        closeSidebar();
        return;
    }

    if (e.key !== 'Tab') {
        return;
    }

    const focusable = sidebarFocusableElements();

    if (focusable.length === 0) {
        e.preventDefault();
        sidebar.focus();
        return;
    }

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
    }
});

window.matchMedia('(min-width: 1024px)').addEventListener('change', (event) => {
    if (event.matches) {
        closeSidebar(false);
    }
});

document.querySelectorAll('[data-dismiss]').forEach((btn) => {
    btn.addEventListener('click', () => btn.closest('[role="status"]')?.remove());
});

const accountMenu = document.querySelector('.cq-account-menu');

document.addEventListener('click', (event) => {
    if (accountMenu?.open && !accountMenu.contains(event.target)) {
        accountMenu.removeAttribute('open');
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && accountMenu?.open) {
        accountMenu.removeAttribute('open');
        accountMenu.querySelector('summary')?.focus();
    }
});
