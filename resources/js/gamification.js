const numberFormat = new Intl.NumberFormat('en-US');

function prefersReducedMotion() {
    return globalThis.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches ?? false;
}

/**
 * System-wide gamification motion.
 *
 * XP is server-rendered; this layer only animates a change that already
 * happened (page navigation or an inline challenge action). Completion XP
 * uses the values embedded by the server. Every effect is optional and
 * skipped under prefers-reduced-motion.
 */
export function initializeGamification(root = document) {
    initializeHeaderXp(root);
    initializeCompletionCelebration(root);
}

function initializeHeaderXp(root) {
    const header = root.querySelector('.atlas-xp[data-xp-value]');
    if (!header) return;

    const storageKey = `cq:xp:last:${header.dataset.xpUser || 'anonymous'}`;
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

    let token = 0;

    const store = (value) => {
        try {
            globalThis.sessionStorage?.setItem(storageKey, String(value));
        } catch { /* Storage is optional. */ }
    };

    const render = (value) => {
        const rounded = Math.max(0, Math.round(value));
        amount().textContent = numberFormat.format(rounded);
        header.dataset.xpValue = String(rounded);
        header.setAttribute('aria-label', `XP balance ${numberFormat.format(rounded)}. Open XP ledger`);
    };

    const float = (delta) => {
        if (!delta) return;
        const bubble = document.createElement('span');
        bubble.className = 'cq-xp-float' + (delta < 0 ? ' cq-xp-float--loss' : '');
        bubble.textContent = `${delta > 0 ? '+' : ''}${numberFormat.format(delta)} XP`;
        bubble.setAttribute('aria-hidden', 'true');
        header.append(bubble);
        globalThis.setTimeout(() => bubble.remove(), 1500);
    };

    const animateTo = (from, to) => {
        const currentToken = ++token;
        const delta = to - from;
        if (!delta) {
            render(to);
            store(to);
            return;
        }

        if (prefersReducedMotion()) {
            render(to);
            store(to);
            return;
        }

        render(from);
        header.classList.remove('is-xp-gain', 'is-xp-loss');
        header.classList.add(delta > 0 ? 'is-xp-gain' : 'is-xp-loss');
        float(delta);
        store(to);

        const started = performance.now();
        const duration = 720;
        const step = (now) => {
            if (currentToken !== token) return;
            const progress = Math.min(1, (now - started) / duration);
            const eased = 1 - Math.pow(1 - progress, 3);
            render(from + (to - from) * eased);
            if (progress < 1) {
                globalThis.requestAnimationFrame(step);
                return;
            }
            render(to);
            globalThis.setTimeout(() => header.classList.remove('is-xp-gain', 'is-xp-loss'), 450);
        };
        globalThis.requestAnimationFrame(step);
    };

    const target = Number(header.dataset.xpValue);
    if (!Number.isFinite(target)) return;

    let previous = null;
    try {
        const raw = globalThis.sessionStorage?.getItem(storageKey);
        if (raw !== null && raw !== undefined) previous = Number(raw);
    } catch { /* Storage is optional. */ }

    if (previous !== null && Number.isFinite(previous) && previous !== target) {
        render(previous);
        globalThis.requestAnimationFrame(() => animateTo(previous, target));
    } else {
        render(target);
        store(target);
    }

    window.addEventListener('cq:xp-updated', (event) => {
        const next = Number(event.detail?.xp);
        const current = Number(header.dataset.xpValue);
        if (!Number.isFinite(next)) return;
        if (!Number.isFinite(current)) {
            render(next);
            store(next);
            return;
        }
        if (next === current) {
            render(current);
            store(current);
            return;
        }
        animateTo(current, next);
    });
}

function initializeCompletionCelebration(root) {
    const mount = (overlay) => {
        if (!(overlay instanceof Element) || overlay.dataset.cqCelebrated === 'true') return;
        overlay.dataset.cqCelebrated = 'true';

        const dialog = overlay.querySelector('[data-completion-dialog]');
        dialog?.classList.add('is-celebrating');
        if (!dialog || prefersReducedMotion()) return;

        const awarded = Number(overlay.dataset.xpAwarded);
        const total = Number(overlay.dataset.xpTotal);
        const awardedEl = overlay.querySelector('[data-completion-awarded]');
        const totalEl = overlay.querySelector('[data-completion-total]');

        if (awardedEl && Number.isFinite(awarded)) {
            countTo(0, awarded, 760, (value) => {
                awardedEl.textContent = `+${numberFormat.format(Math.round(value))}`;
            });
        }

        if (totalEl && Number.isFinite(total)) {
            countTo(Math.max(0, total - (Number.isFinite(awarded) ? awarded : 0)), total, 760, (value) => {
                totalEl.textContent = numberFormat.format(Math.round(value));
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

function countTo(from, to, duration, render) {
    const started = performance.now();
    const step = (now) => {
        const progress = Math.min(1, (now - started) / duration);
        const eased = 1 - Math.pow(1 - progress, 3);
        render(from + (to - from) * eased);
        if (progress < 1) globalThis.requestAnimationFrame(step);
    };
    globalThis.requestAnimationFrame(step);
}
