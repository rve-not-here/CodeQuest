/**
 * Small, server-authoritative motion helpers.
 *
 * These never decide game state. Callers pass the final value already in the
 * server response; the helpers only animate toward it. Reduced motion is read
 * at call time and watched for changes; every helper ends on the final value.
 */

export const MOTION_MICRO = 160;
export const MOTION_UI = 240;
export const MOTION_REWARD = 600;

const reduceQuery = () => globalThis.matchMedia?.('(prefers-reduced-motion: reduce)');

export function prefersReducedMotion() {
    return reduceQuery()?.matches ?? false;
}

export function onMotionPreferenceChange(callback) {
    const query = reduceQuery();
    if (!query?.addEventListener) return () => {};

    const handler = () => callback(query.matches);
    query.addEventListener('change', handler);
    return () => query.removeEventListener('change', handler);
}

/**
 * Count from one server-confirmed value to another. The final value is written
 * to the DOM immediately as data-motion-final/aria-label, and again when the
 * animation ends or is cancelled. No live region is used, so only the final
 * value is available to assistive technology.
 */
export function countUp(element, {
    from = 0,
    to = 0,
    duration = MOTION_REWARD,
    format = (value) => String(Math.round(value)),
    finalLabel = null,
    onDone = null,
} = {}) {
    if (!element) return () => {};

    const finalText = format(to);
    element.dataset.motionFinal = finalText;
    if (finalLabel) element.setAttribute('aria-label', finalLabel);

    let finished = false;
    let frame = 0;
    let stopListening = () => {};

    const finish = () => {
        if (finished) return;
        finished = true;
        globalThis.cancelAnimationFrame(frame);
        element.textContent = finalText;
        if (finalLabel) element.setAttribute('aria-label', finalLabel);
        stopListening();
        onDone?.();
    };

    if (prefersReducedMotion() || from === to || duration <= 0) {
        finish();
        return () => {};
    }

    const started = performance.now();
    const step = (now) => {
        const progress = Math.min(1, (now - started) / duration);
        const eased = 1 - Math.pow(1 - progress, 3);
        element.textContent = format(from + (to - from) * eased);
        if (progress < 1) {
            frame = globalThis.requestAnimationFrame(step);
            return;
        }
        finish();
    };

    frame = globalThis.requestAnimationFrame(step);
    stopListening = onMotionPreferenceChange((reduced) => {
        if (reduced) finish();
    });

    return () => finish();
}

/** Reveal once when an element enters the viewport. */
export function reveal(element, { className = 'is-revealed', threshold = 0.12 } = {}) {
    if (!element) return;
    element.classList.add('cq-reveal');

    if (prefersReducedMotion() || typeof IntersectionObserver === 'undefined') {
        element.classList.add(className);
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        if (!entries.some((entry) => entry.isIntersecting)) return;
        element.classList.add(className);
        observer.disconnect();
    }, { threshold });
    observer.observe(element);
}

/** One short emphasis pulse; it removes itself and never loops. */
export function pulse(element, { className = 'cq-motion-pulse', duration = MOTION_UI } = {}) {
    if (!element || prefersReducedMotion()) return;

    element.classList.remove(className);
    void element.offsetWidth;
    element.classList.add(className);
    globalThis.setTimeout(() => element.classList.remove(className), duration);
}
