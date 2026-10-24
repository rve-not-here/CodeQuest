import assert from 'node:assert/strict';
import test from 'node:test';
import { countUp, pulse, prefersReducedMotion, reveal } from '../resources/js/motion.js';

function withReducedMotion(run) {
    const original = globalThis.matchMedia;
    globalThis.matchMedia = () => ({
        matches: true,
        addEventListener() {},
        removeEventListener() {},
    });

    try {
        return run();
    } finally {
        globalThis.matchMedia = original;
    }
}

test('countUp preserves the final server value under reduced motion', () => {
    withReducedMotion(() => {
        const element = {
            dataset: {},
            textContent: 'old',
            attributes: {},
            setAttribute(name, value) { this.attributes[name] = value; },
        };

        assert.equal(prefersReducedMotion(), true);
        countUp(element, {
            from: 10,
            to: 42,
            format: (value) => String(Math.round(value)),
            finalLabel: '42 XP',
        });

        assert.equal(element.textContent, '42');
        assert.equal(element.dataset.motionFinal, '42');
        assert.equal(element.attributes['aria-label'], '42 XP');
    });
});

test('pulse and reveal become immediate state changes under reduced motion', () => {
    withReducedMotion(() => {
        const pulseElement = {
            classList: {
                values: new Set(),
                add(value) { this.values.add(value); },
                remove(value) { this.values.delete(value); },
            },
            get offsetWidth() { return 1; },
        };
        pulse(pulseElement, { className: 'is-pulsing' });
        assert.equal(pulseElement.classList.values.has('is-pulsing'), false);

        const revealElement = {
            classList: {
                values: new Set(),
                add(value) { this.values.add(value); },
            },
        };
        reveal(revealElement);
        assert.equal(revealElement.classList.values.has('cq-reveal'), true);
        assert.equal(revealElement.classList.values.has('is-revealed'), true);
    });
});
