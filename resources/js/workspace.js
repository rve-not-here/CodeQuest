export function initializeWorkspace(root) {
    const workspace = root.querySelector('[data-workspace]');
    if (!workspace) return;
    const controls = root.querySelector('[data-workspace-controls]');
    const panes = [...workspace.querySelectorAll('[data-workspace-pane]')];
    const separators = [...workspace.querySelectorAll('[data-workspace-resize]')];
    const buttons = [...controls.querySelectorAll('[data-pane-toggle]')];
    const narrow = window.matchMedia('(max-width: 1100px)');
    const widths = { brief: 25, preview: 30 };
    const collapsed = new Set();
    let selected = 'editor';

    function render() {
        for (const pane of panes) {
            const name = pane.dataset.workspacePane;
            pane.hidden = narrow.matches ? name !== selected : collapsed.has(name);
        }
        for (const button of buttons) {
            const pane = panes.find((item) => item.dataset.workspacePane === button.dataset.paneToggle);
            button.setAttribute('aria-expanded', String(!pane.hidden));
        }
        for (const separator of separators) {
            const name = separator.dataset.workspaceResize;
            separator.hidden = narrow.matches || collapsed.has(name);
            separator.setAttribute('aria-valuenow', String(widths[name]));
            separator.setAttribute('aria-valuetext', `${widths[name]} percent`);
        }
        workspace.style.setProperty('--brief-width', collapsed.has('brief') ? '0px' : `${widths.brief}%`);
        workspace.style.setProperty('--preview-width', collapsed.has('preview') ? '0px' : `${widths.preview}%`);
        workspace.style.setProperty('--brief-handle', collapsed.has('brief') ? '0px' : '8px');
        workspace.style.setProperty('--preview-handle', collapsed.has('preview') ? '0px' : '8px');
    }

    function resize(separator, value) {
        const name = separator.dataset.workspaceResize;
        const other = name === 'brief' ? 'preview' : 'brief';
        const minimum = Number(separator.getAttribute('aria-valuemin'));
        const maximum = Math.min(Number(separator.getAttribute('aria-valuemax')), 68 - (collapsed.has(other) ? 0 : widths[other]));
        widths[name] = Math.round(Math.max(minimum, Math.min(maximum, value)));
        render();
    }

    for (const button of buttons) {
        button.addEventListener('click', () => {
            const name = button.dataset.paneToggle;
            if (narrow.matches) selected = name;
            else if (name !== 'editor') {
                if (collapsed.has(name)) collapsed.delete(name);
                else collapsed.add(name);
            }
            render();
        });
    }
    controls.querySelector('[data-workspace-reset]').addEventListener('click', () => {
        widths.brief = 25;
        widths.preview = 30;
        collapsed.clear();
        selected = 'editor';
        render();
    });
    for (const separator of separators) {
        separator.addEventListener('keydown', (event) => {
            const name = separator.dataset.workspaceResize;
            let value = widths[name];
            if (event.key === 'Home') value = Number(separator.getAttribute('aria-valuemin'));
            else if (event.key === 'End') value = Number(separator.getAttribute('aria-valuemax'));
            else if (event.key === 'ArrowLeft') value += name === 'preview' ? 2 : -2;
            else if (event.key === 'ArrowRight') value += name === 'preview' ? -2 : 2;
            else return;
            event.preventDefault();
            resize(separator, value);
        });
        let drag = null;
        separator.addEventListener('pointerdown', (event) => {
            if (event.button !== 0) return;
            drag = { pointer: event.pointerId, x: event.clientX, width: widths[separator.dataset.workspaceResize] };
            separator.setPointerCapture(event.pointerId);
            separator.focus();
            event.preventDefault();
        });
        separator.addEventListener('pointermove', (event) => {
            if (!drag || drag.pointer !== event.pointerId) return;
            const direction = separator.dataset.workspaceResize === 'preview' ? -1 : 1;
            resize(separator, drag.width + direction * (event.clientX - drag.x) / workspace.getBoundingClientRect().width * 100);
        });
        function stop() { drag = null; }
        separator.addEventListener('pointerup', stop);
        separator.addEventListener('pointercancel', stop);
        separator.addEventListener('lostpointercapture', stop);
    }
    narrow.addEventListener('change', render);
    render();
}
