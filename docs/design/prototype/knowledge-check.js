/*
 * Knowledge Check — prototype only.
 *
 * There is no server here. Answers live in the native radios, the answer key
 * lives on each fieldset as `data-correct`, and scoring happens in this file.
 * Nothing is persisted, so a reload starts again.
 *
 * Shape: one `setState` function owns which panel is visible, and the
 * answering / review / result panels all reuse the same question DOM. There is
 * no second copy of the questions to drift out of sync.
 */

const STATES = ['question', 'review', 'passed', 'failed', 'answers', 'locked', 'loading', 'error'];
const PASS_RATIO = 0.8;

const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector) => [...document.querySelectorAll(selector)];

const params = new URLSearchParams(location.search);
const requested = params.get('state');
// An unrecognised value falls back to the canonical locked view rather than
// rendering nothing.
const initial = STATES.includes(requested) ? requested : 'locked';

const panels = $$('[data-state-panel]');
const stateLinks = $$('[data-state-link]');
const answering = $('[data-state-panel="answering"]');
const questionStack = $('[data-question-stack]');
const fieldsets = $$('[data-question]');
const navCells = $$('[data-goto]');
const prevButton = $('[data-prev]');
const nextButton = $('[data-next]');
const nextLabel = $('[data-next-label]');
const submitButtons = $$('[data-submit]');
const reasonNodes = [$('[data-submit-reason]'), $('[data-review-reason]')];
const progressText = $('[data-progress-text]');
const progressRail = $('[data-progress-rail]');
const reviewSummary = $('[data-review-summary]');
const reviewList = $('[data-review-list]');
const dialog = $('[data-confirm-dialog]');
const toggleAnswers = $('[data-toggle-answers]');

const TOTAL = fieldsets.length;

/* Deterministic seeds for the prototype result states. The pass set misses
   Q2; the fail set misses Q2, Q3 and Q4. */
const SEEDS = {
    passed: { q1: 'border-box', q2: '368px', q3: 'margin', q4: '24px', q5: '268px' },
    // 2 of 5 correct: Q1 and Q5, with Q2, Q3 and Q4 wrong.
    failed: { q1: 'border-box', q2: '322px', q3: 'padding', q4: '48px', q5: '268px' },
};
SEEDS.answers = SEEDS.passed;

let current = 1;
let state = 'question';

const numberOf = (fieldset) => Number(fieldset.dataset.question);
const fieldsetFor = (n) => fieldsets[n - 1];
const chosenOf = (fieldset) => fieldset.querySelector('input:checked')?.value ?? null;
const isAnswered = (fieldset) => chosenOf(fieldset) !== null;
const answeredCount = () => fieldsets.filter(isAnswered).length;
const isComplete = () => answeredCount() === TOTAL;

function optionText(fieldset, value) {
    const input = fieldset.querySelector(`input[value="${CSS.escape(value)}"]`);
    return input?.closest('label')?.querySelector('span')?.textContent.trim() ?? value;
}

/* ── Progress ─────────────────────────────────────────────────────── */

let lastProgressLabel = '';

function syncProgress() {
    const answered = answeredCount();
    const label = `${answered} of ${TOTAL} answered`;

    // Only write when the value actually changes. Rewriting an unchanged
    // live region on every navigation would re-announce it for nothing.
    if (label !== lastProgressLabel) {
        lastProgressLabel = label;
        progressText.textContent = label;
    }

    progressRail.setAttribute('aria-valuenow', String(answered));
    progressRail.firstElementChild.style.width = `${(answered / TOTAL) * 100}%`;

    for (const cell of navCells) {
        const n = Number(cell.dataset.goto);
        const answeredHere = isAnswered(fieldsetFor(n));
        cell.dataset.answered = String(answeredHere);
        cell.setAttribute(
            'aria-label',
            `Question ${n}, ${n === current ? 'current, ' : ''}${answeredHere ? 'answered' : 'unanswered'}`,
        );
    }

    for (const button of submitButtons) {
        button.disabled = !isComplete();
    }
    for (const reason of reasonNodes) {
        reason.textContent = isComplete()
            ? 'Ready to submit.'
            : `Answer all ${TOTAL} questions before submitting.`;
    }
    if (reviewSummary) {
        reviewSummary.textContent = isComplete()
            ? `All ${TOTAL} questions answered.`
            : `${answered} of ${TOTAL} answered. ${TOTAL - answered} unanswered.`;
    }
    if (state === 'review') {
        buildReviewList();
    }
}

/* ── Question navigation ──────────────────────────────────────────── */

function showQuestion(n, { moveFocus = true } = {}) {
    current = Math.min(Math.max(n, 1), TOTAL);

    for (const fieldset of fieldsets) {
        fieldset.hidden = numberOf(fieldset) !== current;
    }
    for (const cell of navCells) {
        if (Number(cell.dataset.goto) === current) {
            cell.setAttribute('aria-current', 'step');
        } else {
            cell.removeAttribute('aria-current');
        }
    }

    prevButton.disabled = current === 1;
    const isLast = current === TOTAL;
    nextLabel.textContent = isLast ? 'Review Answers' : 'Next';
    nextButton.setAttribute(
        'aria-label',
        isLast ? 'Go to review answers' : `Next question, question ${current + 1} of ${TOTAL}`,
    );

    // Focus follows the content when the student moves, because the button
    // they pressed has just changed meaning. Never on first paint: focusing a
    // tabindex="-1" container with no user intent behind it paints a focus
    // ring around the whole question.
    if (moveFocus) {
        const fieldset = fieldsetFor(current);
        fieldset.focus({ preventScroll: true });
        fieldset.scrollIntoView({ block: 'nearest' });
    }

    syncProgress();
}

/* ── Review checklist ─────────────────────────────────────────────── */

function buildReviewList() {
    reviewList.replaceChildren();

    for (const fieldset of fieldsets) {
        const n = numberOf(fieldset);
        const answered = isAnswered(fieldset);

        const item = document.createElement('li');
        item.className = 'kc-checklist-item';

        const mark = document.createElement('span');
        mark.className = answered
            ? 'kc-legend-mark border-accent-line bg-accent-soft text-accent'
            : 'kc-legend-mark border-line-strong text-fg-subtle';
        mark.setAttribute('aria-hidden', 'true');
        if (answered) {
            mark.innerHTML = '<svg class="size-3"><use href="#i-check" /></svg>';
        } else {
            mark.textContent = String(n);
        }

        const status = document.createElement('span');
        status.className = answered ? 'text-fg' : 'text-fg-subtle';
        status.textContent = `Question ${n} · ${answered ? 'Answered' : 'Unanswered'}`;

        const go = document.createElement('button');
        go.type = 'button';
        go.className = 'btn btn-quiet btn-sm ml-auto';
        go.textContent = answered ? 'Review' : 'Answer';
        go.setAttribute('aria-label', `${answered ? 'Review' : 'Answer'} question ${n}`);
        go.addEventListener('click', () => setState('question', { focusQuestion: n }));

        item.append(mark, status, go);
        reviewList.append(item);
    }
}

/* ── Answer review after submission ───────────────────────────────── */

function buildAnswerReview() {
    // The submitted question DOM becomes the review, so the question text is
    // never copied into a parallel structure that could drift.
    for (const fieldset of fieldsets) {
        const chosen = chosenOf(fieldset);
        const correct = fieldset.dataset.correct;
        const right = chosen === correct;

        for (const input of fieldset.querySelectorAll('input')) {
            input.disabled = true;
        }
        for (const label of fieldset.querySelectorAll('.answer-option')) {
            const value = label.querySelector('input').value;
            label.removeAttribute('data-verdict');
            label.querySelector('use').setAttribute('href', value === chosen && !right ? '#i-x' : '#i-check');
            if (value === chosen) {
                label.dataset.verdict = right ? 'correct' : 'incorrect';
            }
        }

        const block = $('[data-verdict-block]', fieldset);
        block.hidden = false;

        const badge = $('[data-verdict-label]', block);
        badge.className = `badge ${right ? 'badge-accent' : 'badge-danger'}`;
        badge.textContent = right ? 'Correct' : 'Incorrect';

        const list = $('dl', block);
        $('[data-chosen]', list).textContent = chosen === null ? 'Not answered' : optionText(fieldset, chosen);

        // Only show the correct answer when the student was wrong, so the
        // review always carries what was missed.
        let correctRow = $('[data-correct-row]', list);
        if (!right) {
            if (!correctRow) {
                correctRow = document.createElement('div');
                correctRow.dataset.correctRow = '';
                correctRow.innerHTML = '<dt>Correct answer</dt><dd></dd>';
                list.append(correctRow);
            }
            $('dd', correctRow).textContent = optionText(fieldset, correct);
            correctRow.hidden = false;
        } else if (correctRow) {
            correctRow.hidden = true;
        }
    }
}

function score() {
    const right = fieldsets.filter((fieldset) => chosenOf(fieldset) === fieldset.dataset.correct).length;
    return { right, percent: Math.round((right / TOTAL) * 100), passed: right / TOTAL >= PASS_RATIO };
}

function renderResult() {
    const { right, percent, passed } = score();

    $('[data-result-score]').textContent = `${right} / ${TOTAL}`;
    $('[data-result-percent]').textContent = String(percent);

    const badge = $('[data-result-badge]');
    badge.className = `badge ${passed ? 'badge-accent' : 'badge-warning'}`;
    badge.textContent = passed ? 'Passed' : 'Needs another attempt';

    $('[data-result-eyebrow]').textContent = passed ? 'KC 02 · Passed' : 'KC 02 · Attempt 1';
    $('[data-result-title]').textContent = passed ? 'Knowledge Check complete' : 'Not passed yet';
    $('[data-result-copy]').textContent = passed
        ? 'You cleared KC 02. Layout with Flexbox is the next section.'
        : 'Review the CSS Selectors and the Box Model section before trying again.';

    const primary = $('[data-result-primary]');
    $('[data-result-primary-label]').textContent = passed ? 'Continue Learning' : 'Review Section';
    primary.href = passed ? 'learn.html#section-03' : 'learn.html#section-02';

    const secondary = $('[data-result-secondary]');
    secondary.textContent = passed ? 'Back to Missions' : 'Try Again';
    secondary.href = passed ? 'missions.html' : 'knowledge-check.html?state=question';
}

/* ── State machine ────────────────────────────────────────────────── */

const PANELS_FOR = {
    question: ['answering'],
    review: ['review'],
    passed: ['result', 'answering'],
    failed: ['result', 'answering'],
    answers: ['result', 'answering'],
    locked: ['locked'],
    loading: ['loading'],
    error: ['error'],
};

function setState(next, { focusQuestion = null, push = true } = {}) {
    if (next === 'passed' || next === 'failed') {
        next = score().passed ? 'passed' : 'failed';
    }
    state = next;
    const contextOnly = ['locked', 'loading', 'error'].includes(next);
    $('[data-check-preview-note]').hidden = contextOnly;
    $('[data-attempt-label]').textContent = contextOnly ? 'Status' : 'Preview attempt';
    $('[data-attempt-value]').textContent = next === 'locked' ? 'Locked / Not attempted' : next === 'loading' ? 'Loading' : next === 'error' ? 'Unavailable' : '1';
    if (push && contextOnly) {
        $('#knowledge-check-title').focus();
    }
    const shown = PANELS_FOR[next] ?? ['answering'];

    for (const panel of panels) {
        panel.hidden = !shown.includes(panel.dataset.statePanel);
    }

    // Grading mode is driven by the result panel, not by the answering panel:
    // both are shown together in a result state, and only the answering panel
    // is shown while answering.
    const graded = shown.includes('result');
    answering.dataset.review = graded ? 'answers' : 'answering';

    if (graded) {
        // Radios stop accepting input once the check is graded.
        for (const fieldset of fieldsets) {
            fieldset.hidden = false;
            for (const input of fieldset.querySelectorAll('input')) {
                input.disabled = true;
            }
        }
        renderResult();
        buildAnswerReview();
    } else {
        for (const block of $$('[data-verdict-block]')) {
            block.hidden = true;
        }
        for (const label of $$('.answer-option[data-verdict]')) {
            label.removeAttribute('data-verdict');
        }
        for (const input of $$('[data-question] input')) {
            input.disabled = false;
        }
    }

    if (next === 'review') {
        buildReviewList();
        if (push) {
            $('#review-title').focus();
        }
    } else if (next === 'question') {
        showQuestion(focusQuestion ?? current, { moveFocus: push });
    }

    if (graded && push) {
        $('#result-title').focus();
    }

    for (const link of stateLinks) {
        if (link.dataset.stateLink === next) {
            link.setAttribute('aria-current', 'true');
        } else {
            link.removeAttribute('aria-current');
        }
    }

    syncProgress();

    if (push) {
        const url = next === 'locked' ? location.pathname : `${location.pathname}?state=${next}`;
        history.replaceState(null, '', url);
    }
}

/* ── Wiring ───────────────────────────────────────────────────────── */

for (const fieldset of fieldsets) {
    for (const input of fieldset.querySelectorAll('input')) {
        input.addEventListener('change', syncProgress);
    }
}

for (const cell of navCells) {
    cell.addEventListener('click', () => {
        if (answering.dataset.review === 'answering') {
            showQuestion(Number(cell.dataset.goto));
        }
    });
}

prevButton.addEventListener('click', () => showQuestion(current - 1));

nextButton.addEventListener('click', () => {
    // Next on the last question goes to review. Review is also reachable at
    // any time from the sidebar, so this is a shortcut rather than a gate.
    setState(current === TOTAL ? 'review' : 'question', { focusQuestion: current === TOTAL ? null : current + 1 });
});

$('[data-review-open]').addEventListener('click', () => setState('review'));
$('[data-back-to-questions]').addEventListener('click', () => setState('question'));

for (const button of submitButtons) {
    button.addEventListener('click', () => {
        // Submission requires a complete check, here and in review alike.
        if (!isComplete()) {
            return;
        }
        if (!dialog.open) {
            dialog.returnValue = '';
            dialog.showModal();
        }
    });
}

// Only the return value decides what happens. Focus is left entirely to the
// dialog element: a manual restore in this handler races the browser's own
// restoration and loses.
dialog.addEventListener('close', () => {
    if (dialog.returnValue === 'confirm') {
        setState('passed');
    }
});

toggleAnswers.addEventListener('click', () => {
    // The answer review always sits below the result panel, so this only has
    // to take the student to it.
    fieldsets[0].focus({ preventScroll: true });
    questionStack.scrollIntoView({ block: 'start' });
});

// "Try again" on the error panel is a control, not a link, so it can reset
// without leaving the prototype.
for (const control of $$('[data-state-goto]')) {
    control.addEventListener('click', () => setState(control.dataset.stateGoto));
}

/* ── Boot ─────────────────────────────────────────────────────────── */

if (SEEDS[initial]) {
    for (const [name, value] of Object.entries(SEEDS[initial])) {
        const input = document.querySelector(`input[name="${name}"][value="${CSS.escape(value)}"]`);
        if (input) {
            input.checked = true;
        }
    }
}

// Clear browser-restored form values so a retry starts a fresh prototype attempt.
if (!SEEDS[initial]) {
    for (const input of $$('[data-question] input')) {
        input.checked = false;
    }
}

// First paint must not steal focus, so question 1 is shown without moving it.
showQuestion(1, { moveFocus: false });
setState(initial, { push: false });

if (initial === 'answers') {
    questionStack.scrollIntoView({ block: 'start' });
}
