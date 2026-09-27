const menu = document.querySelector('[data-menu]');
const trigger = menu?.querySelector('[data-menu-trigger]');
const panel = menu?.querySelector('[data-menu-panel]');

function menuLinks() {
    return [...(panel?.querySelectorAll('a[href]') ?? [])].filter((link) => link.offsetParent !== null);
}

function setMenuOpen(isOpen, { returnFocus = false } = {}) {
    if (!trigger || !panel) {
        return;
    }

    trigger.setAttribute('aria-expanded', String(isOpen));
    panel.hidden = !isOpen;

    if (isOpen) {
        menuLinks()[0]?.focus();
    } else if (returnFocus) {
        trigger.focus();
    }
}

trigger?.addEventListener('click', () => {
    setMenuOpen(trigger.getAttribute('aria-expanded') !== 'true');
});

document.addEventListener('click', (event) => {
    if (panel && !panel.hidden && !menu.contains(event.target)) {
        setMenuOpen(false);
    }
});

menu?.addEventListener('keydown', (event) => {
    if (panel.hidden) {
        return;
    }

    if (event.key === 'Escape') {
        event.preventDefault();
        setMenuOpen(false, { returnFocus: true });
        return;
    }

    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();
        const links = menuLinks();
        const index = links.indexOf(document.activeElement);
        const step = event.key === 'ArrowDown' ? 1 : -1;
        links[(index + step + links.length) % links.length]?.focus();
    }
});

menu?.addEventListener('focusout', (event) => {
    if (panel && !panel.hidden && event.relatedTarget && !menu.contains(event.relatedTarget)) {
        setMenuOpen(false);
    }
});
