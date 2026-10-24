import {
    MOTION_REWARD,
    countUp,
    onMotionPreferenceChange,
    prefersReducedMotion,
    pulse,
} from './motion.js';

const numberFormat = new Intl.NumberFormat('en-US');

/**
 * System-wide gamification motion.
 *
 * The server renders the final XP, scores and completion values. This module
 * only replays a change carried by the response (or an inline event carrying
 * the server's new balance); it never computes XP, deltas, progress or unlocks.
 * Nothing replays on a normal page load.
 */
export function initializeGamification(root = document) {
    const header = initializeHeaderXp(root);
    initializeCompletionCelebration(root, header);
    initializeDashboardCounts(root);
    initializeEditorReveal();
    initializeMotionPreferenceCleanup();
}

function initializeHeaderXp(root) {
    const header = root.querySelector('.atlas-xp[data-xp-value]');
    if (!header) return null;

    const amount = () => {
        let target = header.querySelector('[data-xp-amount]');
        if (target) return target;

        header.textContent = '';
        target = document.createElement('span');
        target.dataset.xpAmount = '';
        const unit = document.createElement('span');
        unit.textContent = 'XP';
        header.append(target, document.createTextNode(' '), unit);
        return target;
    };

    const render = (value) => {
        const rounded = Math.max(0, Math.round(value));
        amount().textContent = numberFormat.format(rounded);
        header.dataset.xpValue = String(rounded);
        header.setAttribute('aria-label', `XP balance ${numberFormat.format(rounded)}. Open XP ledger`);
    };

    const target = Number(header.dataset.xpValue);
    if (!Number.isFinite(target)) return null;
    render(target);

    window.addEventListener('cq:xp-updated', (event) => {
        const next = Number(event.detail?.xp);
        const current = Number(header.dataset.xpValue);
        if (!Number.isFinite(next)) return;

        if (!Number.isFinite(current) || next === current) {
            render(next);
            return;
        }

        const from = current;
        header.dataset.xpValue = String(next);
        header.dataset.cqSyncedXp = String(next);
        header.setAttribute('aria-label', `XP balance ${numberFormat.format(next)}. Open XP ledger`);
        countUp(amount(), {
            from,
            to: next,
            duration: MOTION_REWARD,
            format: (value) => numberFormat.format(Math.round(value)),
            finalLabel: `XP balance ${numberFormat.format(next)}`,
            onDone: () => render(next),
        });
        pulse(header, { className: 'is-xp-updated', duration: MOTION_REWARD });
    });

    return header;
}

function initializeCompletionCelebration(root, header) {
    const mount = (overlay) => {
        if (!(overlay instanceof Element) || overlay.dataset.cqCelebrated === 'true') return;
        overlay.dataset.cqCelebrated = 'true';

        const dialog = overlay.querySelector('[data-completion-dialog]');
        dialog?.classList.add('is-celebrating');
        const awarded = Number(overlay.dataset.xpAwarded);
        const total = Number(overlay.dataset.xpTotal);

        animateCompletionProgress(overlay);

        if (header && Number.isFinite(total) && Number.isFinite(awarded) && awarded > 0 && header.dataset.cqSyncedXp !== String(total)) {
            const amount = header.querySelector('[data-xp-amount]');
            const from = Math.max(0, total - awarded);
            header.dataset.xpValue = String(total);
            header.dataset.cqSyncedXp = String(total);
            header.setAttribute('aria-label', `XP balance ${numberFormat.format(total)}. Open XP ledger`);
            countUp(amount, {
                from,
                to: total,
                duration: MOTION_REWARD,
                format: (value) => numberFormat.format(Math.round(value)),
                finalLabel: `XP balance ${numberFormat.format(total)}`,
            });
        }

        if (header && Number.isFinite(awarded) && awarded > 0) {
            pulse(header, { className: 'is-xp-updated', duration: MOTION_REWARD });
        }
        if (!dialog || prefersReducedMotion()) return;

        const awardedEl = overlay.querySelector('[data-completion-awarded]');
        const totalEl = overlay.querySelector('[data-completion-total]');

        if (awardedEl && Number.isFinite(awarded)) {
            countUp(awardedEl, {
                from: 0,
                to: awarded,
                duration: MOTION_REWARD,
                format: (value) => `+${numberFormat.format(Math.round(value))}`,
                finalLabel: `${numberFormat.format(awarded)} XP earned`,
            });
        }

        if (totalEl && Number.isFinite(total)) {
            const start = Math.max(0, total - (Number.isFinite(awarded) ? awarded : 0));
            countUp(totalEl, {
                from: start,
                to: total,
                duration: MOTION_REWARD,
                format: (value) => numberFormat.format(Math.round(value)),
                finalLabel: `Total ${numberFormat.format(total)} XP`,
            });
        }
    };

    root.querySelectorAll?.('[data-completion-overlay]').forEach(mount);

    if (!root.body) return;
    const observer = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            for (const node of mutation.addedNodes) {
                if (!(node instanceof Element)) continue;
                if (node.matches('[data-completion-overlay]')) mount(node);
                node.querySelectorAll?.('[data-completion-overlay]').forEach(mount);
            }
        }
    });
    observer.observe(root.body, { childList: true, subtree: true });
}

function animateCompletionProgress(overlay) {
    const meter = overlay.querySelector('[data-completion-progress]');
    const fill = meter?.querySelector('.completion-progress-bar > span');
    if (!meter || !fill) return;

    const before = Number(meter.dataset.before);
    const after = Number(meter.dataset.after);
    if (!Number.isFinite(before) || !Number.isFinite(after) || after <= 0) return;

    fill.style.width = `${after}%`;
    if (prefersReducedMotion() || before >= after) return;

    const startScale = Math.min(1, Math.max(0, before / after));
    fill.style.transformOrigin = 'left center';
    fill.style.transform = `scaleX(${startScale})`;
    window.requestAnimationFrame(() => {
        fill.style.transition = 'transform 600ms cubic-bezier(.23, 1, .32, 1)';
        fill.style.transform = 'scaleX(1)';
    });
}

function initializeDashboardCounts(root) {
    root.querySelectorAll('[data-countup]').forEach((element) => {
        const target = Number(element.dataset.countup);
        if (!Number.isFinite(target)) return;

        const padded = element.dataset.countupFormat === 'pad2';
        const format = (value) => {
            const rounded = Math.max(0, Math.round(value));
            return padded ? String(rounded).padStart(2, '0') : numberFormat.format(rounded);
        };

        countUp(element, {
            from: 0,
            to: target,
            duration: MOTION_REWARD,
            format,
            finalLabel: element.dataset.countupLabel || format(target),
        });
    });
}

function initializeEditorReveal() {
    window.addEventListener('cq:reveal-solution', () => {
        const editor = document.querySelector('#editor-host');
        pulse(editor, { className: 'cq-editor-reveal', duration: 240 });
    });

    window.addEventListener('cq:submit-failed', () => {
        const result = document.querySelector('[data-challenge-notice]');
        pulse(result, { className: 'cq-motion-feedback', duration: 240 });
    });
}

function initializeMotionPreferenceCleanup() {
    onMotionPreferenceChange((reduced) => {
        if (!reduced) return;
        document.querySelectorAll('.is-xp-updated, .cq-editor-reveal, .is-celebrating, .cq-motion-feedback').forEach((element) => {
            element.classList.remove('is-xp-updated', 'cq-editor-reveal', 'is-celebrating', 'cq-motion-feedback');
        });
    });
}
