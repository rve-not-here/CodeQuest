import assert from 'node:assert/strict';
import test from 'node:test';
import { initializeKnowledgeCheck } from '../resources/js/knowledge-check.js';

test('answer summary restores selections and links only unanswered questions', () => {
    const questions = Array.from({ length: 5 }, (_, index) => ({
        id: `question-${index + 1}`,
        answered: index < 3,
        querySelector() { return this.answered ? {} : null; },
        focus() { this.focused = true; },
    }));
    const count = {};
    const progress = { attributes: {}, firstElementChild: { style: {} }, setAttribute(key, value) { this.attributes[key] = value; } };
    const unanswered = { children: [], replaceChildren() { this.children = []; }, append(item) { this.children.push(item); } };
    const summary = { hidden: true };
    const form = {
        querySelectorAll: () => questions,
        querySelector: (selector) => ({ '[data-check-count]': count, '[data-check-progress]': progress, '[data-check-unanswered]': unanswered, '[data-check-summary]': summary })[selector],
        addEventListener(name, callback) { this.change = callback; },
    };
    initializeKnowledgeCheck({
        querySelector: () => form,
        createElement: () => ({ append(child) { this.child = child; }, addEventListener(name, callback) { this.click = callback; } }),
    });
    assert.equal(count.textContent, 'Answered 3 of 5');
    assert.equal(progress.attributes['aria-valuenow'], '3');
    assert.equal(progress.firstElementChild.style.width, '60%');
    assert.equal(summary.hidden, false);
    assert.deepEqual(unanswered.children.map((item) => item.child.href), ['#question-4', '#question-5']);
    unanswered.children[0].child.click();
    assert.equal(questions[3].focused, true);
    questions[3].answered = true;
    questions[4].answered = true;
    form.change();
    assert.equal(count.textContent, 'Answered 5 of 5');
    assert.equal(progress.firstElementChild.style.width, '100%');
    assert.equal(unanswered.children.length, 0);
    assert.equal(unanswered.textContent, 'All questions answered.');
});

test('pages without a Knowledge Check need no summary elements', () => {
    initializeKnowledgeCheck({ querySelector: () => null });
});
