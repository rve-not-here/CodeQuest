export function initializeTabbedWorkspace(root, workspace) {
    const controls = root.querySelector('[data-workspace-controls]');
    const panes = [...workspace.querySelectorAll('[data-workspace-pane]')];
    const tabs = [...controls.querySelectorAll('[data-pane-toggle]')];
    const separators = [...workspace.querySelectorAll('[data-workspace-resize]')];
    const reset = root.querySelector('[data-workspace-reset]');
    const hideBrief = root.querySelector('[data-workspace-hide-brief]');
    const widthInput = root.querySelector('[data-workspace-width]');
    const narrow = window.matchMedia('(max-width: 1100px)');
    let selected = 'editor';
    let briefHidden = false;
    let width = 360;

    function maximumWidth() {
        return Math.max(240, Math.min(480, workspace.getBoundingClientRect().width - 408));
    }

    function render() {
        if (!narrow.matches && selected === 'brief') selected = 'editor';
        if (!narrow.matches) width = Math.max(240, Math.min(maximumWidth(), width));
        controls.setAttribute('role', 'tablist');
        for (const pane of panes) {
            const name = pane.dataset.workspacePane;
            pane.hidden = name === 'brief' ? (narrow.matches ? selected !== name : briefHidden) : selected !== name;
            pane.setAttribute('role', name === 'brief' && !narrow.matches ? 'region' : 'tabpanel');
            pane.setAttribute('aria-labelledby', name === 'brief' && !narrow.matches ? 'instructions-title' : `workspace-tab-${name}`);
        }
        for (const tab of tabs) {
            const active = tab.dataset.paneToggle === selected;
            tab.hidden = tab.dataset.paneToggle === 'brief' && !narrow.matches;
            tab.setAttribute('role', 'tab');
            tab.setAttribute('aria-selected', String(active));
            tab.setAttribute('tabindex', active ? '0' : '-1');
            tab.removeAttribute('aria-expanded');
        }
        for (const separator of separators) {
            separator.hidden = separator.dataset.workspaceResize !== 'brief' || narrow.matches || briefHidden;
            separator.setAttribute('aria-valuemin', '240');
            separator.setAttribute('aria-valuemax', String(maximumWidth()));
            separator.setAttribute('aria-valuenow', String(Math.round(width)));
            separator.setAttribute('aria-valuetext', `${Math.round(width)} pixels`);
        }
        workspace.style.setProperty('--brief-width', briefHidden ? '0px' : `${width}px`);
        workspace.style.setProperty('--brief-handle', briefHidden ? '0px' : '8px');
        hideBrief?.setAttribute('aria-expanded', String(!briefHidden));
        const hideLabel = hideBrief?.querySelector('[data-workspace-hide-label]');
        if (hideLabel) hideLabel.textContent = briefHidden ? 'Show instructions' : 'Hide instructions';
        for (const icon of root.querySelectorAll('[data-workspace-hide-icon]')) {
            icon.hidden = icon.dataset.workspaceHideIcon !== (briefHidden ? 'open' : 'closed');
        }
        if (widthInput) {
            widthInput.value = String(Math.round(width));
            widthInput.setAttribute('max', String(maximumWidth()));
        }
        root.dispatchEvent?.(new CustomEvent('cq:workspace-layout'));
    }

    for (const tab of tabs) {
        tab.addEventListener('click', () => { selected = tab.dataset.paneToggle; render(); });
        tab.addEventListener('keydown', (event) => {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
            event.preventDefault();
            const visible = tabs.filter((item) => !item.hidden);
            const index = visible.indexOf(tab);
            const next = event.key === 'Home' ? 0 : event.key === 'End' ? visible.length - 1
                : (index + (event.key === 'ArrowRight' ? 1 : -1) + visible.length) % visible.length;
            selected = visible[next].dataset.paneToggle;
            render();
            visible[next].focus();
        });
    }
    reset?.addEventListener('click', () => {
        width = 360;
        briefHidden = false;
        selected = 'editor';
        render();
    });
    hideBrief?.addEventListener('click', () => { briefHidden = !briefHidden; render(); });
    widthInput?.addEventListener('input', () => { width = Number(widthInput.value); render(); });

    for (const separator of separators.filter((item) => item.dataset.workspaceResize === 'brief')) {
        separator.addEventListener('keydown', (event) => {
            if (event.key === 'Home') width = 240;
            else if (event.key === 'End') width = maximumWidth();
            else if (event.key === 'ArrowLeft') width -= 16;
            else if (event.key === 'ArrowRight') width += 16;
            else return;
            event.preventDefault();
            render();
        });
        let drag = null;
        separator.addEventListener('pointerdown', (event) => {
            if (event.button !== 0) return;
            drag = { pointer: event.pointerId, x: event.clientX, width };
            separator.setPointerCapture(event.pointerId);
            separator.focus();
            event.preventDefault();
        });
        separator.addEventListener('pointermove', (event) => {
            if (!drag || drag.pointer !== event.pointerId) return;
            width = drag.width + event.clientX - drag.x;
            render();
        });
        for (const name of ['pointerup', 'pointercancel', 'lostpointercapture']) {
            separator.addEventListener(name, () => { drag = null; });
        }
    }
    root.addEventListener('cq:workspace-pane', (event) => {
        if (!panes.some((pane) => pane.dataset.workspacePane === event.detail)) return;
        selected = event.detail;
        render();
        if (narrow.matches) tabs.find((tab) => tab.dataset.paneToggle === selected)?.focus();
    });
    narrow.addEventListener('change', render);
    window.addEventListener('resize', render);

    const fullscreenButton = root.querySelector('[data-workspace-fullscreen]');
    const fullscreenRoot = root.querySelector('[data-workspace-fullscreen-root]');
    if (fullscreenButton && fullscreenRoot) {
        fullscreenButton.addEventListener('click', async () => {
            if (root.fullscreenElement) await root.exitFullscreen();
            else await fullscreenRoot.requestFullscreen();
        });
        root.addEventListener('fullscreenchange', () => {
            fullscreenButton.setAttribute('aria-pressed', String(Boolean(root.fullscreenElement)));
            fullscreenButton.querySelector('[data-workspace-fullscreen-label]').textContent = root.fullscreenElement ? 'Exit fullscreen' : 'Fullscreen';
            for (const icon of fullscreenButton.querySelectorAll('[data-workspace-fullscreen-icon]')) {
                icon.hidden = icon.dataset.workspaceFullscreenIcon !== (root.fullscreenElement ? 'exit' : 'enter');
            }
            render();
        });
    }
    render();
}
