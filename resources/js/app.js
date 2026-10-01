const editorHost = document.getElementById('editor-host');

if (editorHost) {
    import('./editor.js').catch((error) => {
        editorHost.setAttribute('role', 'alert');
        editorHost.textContent = 'Editor could not load. Reload this page to try again.';
        console.error('CodeQuest editor failed to load.', error);
    });
}

document.querySelectorAll('[data-dismiss]').forEach((btn) => {
    btn.addEventListener('click', () => btn.closest('[role="status"]')?.remove());
});

// Knowledge checks: the progress rail counts real answers, so a student
// can see how much is left before committing to a submit.
const checkProgress = document.querySelector('[data-check-progress]');
const checkProgressLabel = document.querySelector('[data-check-progress-label]');

if (checkProgress && checkProgressLabel) {
    const total = Number.parseInt(checkProgress.getAttribute('aria-valuemax'), 10) || 0;
    const fill = checkProgress.querySelector('.knowledge-check-progress-fill');

    const syncCheckProgress = () => {
        const answered = document.querySelectorAll('.knowledge-check-option input:checked').length;
        checkProgress.setAttribute('aria-valuenow', String(answered));
        if (fill) {
            fill.style.width = `${total > 0 ? (answered / total) * 100 : 0}%`;
        }
        checkProgressLabel.textContent = `${answered} of ${total} answered`;
    };

    document.querySelectorAll('.knowledge-check-option input').forEach((input) => {
        input.addEventListener('change', syncCheckProgress);
    });

    syncCheckProgress();
}

const accountMenu = document.getElementById('cq-account-menu');

function accountMenuItems() {
    return [...(accountMenu?.querySelectorAll('.cq-account-link, .cq-account-action') ?? [])];
}

function closeAccountMenu({ restoreFocus = false } = {}) {
    if (!accountMenu?.open) {
        return;
    }

    accountMenu.removeAttribute('open');
    if (restoreFocus) {
        accountMenu.querySelector('[data-account-trigger]')?.focus();
    }
}

document.addEventListener('click', (event) => {
    if (accountMenu?.open && !accountMenu.contains(event.target)) {
        closeAccountMenu();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && accountMenu?.open) {
        event.preventDefault();
        closeAccountMenu({ restoreFocus: true });
    }
});

// Arrow-key traversal across the account menu. <details> opens on Enter
// and Space already, but without this the nine destinations in it are
// only reachable by tabbing, and the popover scrolls.
accountMenu?.addEventListener('keydown', (event) => {
    const items = accountMenuItems();
    if (items.length === 0) {
        return;
    }

    const current = items.indexOf(document.activeElement);
    const last = items.length - 1;
    let next = null;

    switch (event.key) {
        case 'ArrowDown':
            next = current < 0 ? 0 : Math.min(current + 1, last);
            break;
        case 'ArrowUp':
            next = current < 0 ? last : Math.max(current - 1, 0);
            break;
        case 'Home':
            next = 0;
            break;
        case 'End':
            next = last;
            break;
        default:
            return;
    }

    event.preventDefault();
    accountMenu.open = true;
    items[next].focus();
});

accountMenu?.addEventListener('focusout', () => {
    window.setTimeout(() => {
        if (!accountMenu.contains(document.activeElement)) closeAccountMenu();
    }, 0);
});
