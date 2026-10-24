export function initializeKnowledgeCheck(root) {
    initializeResultDialogs(root);

    const completed = root.querySelector('[data-check-completed-key]');
    try {
        if (completed?.dataset?.checkCompletedKey) globalThis.sessionStorage?.removeItem(completed.dataset.checkCompletedKey);
    } catch { /* Storage may be unavailable; server attempt state remains authoritative. */ }

    const form = root.querySelector('[data-knowledge-check]');
    if (!form) return;
    const questions = [...form.querySelectorAll('[data-check-question]')];
    const count = form.querySelector('[data-check-count]');
    const progress = form.querySelector('[data-check-progress]');
    const unanswered = form.querySelector('[data-check-unanswered]');

    const draftKey = form.dataset?.draftKey;
    const inputs = draftKey ? [...form.querySelectorAll('input[type="radio"]')] : [];
    try {
        const saved = draftKey ? JSON.parse(globalThis.sessionStorage?.getItem(draftKey) || '{}') : {};
        const restoredByServer = new Set(inputs.filter(input => input.checked).map(input => input.name));
        if (saved && typeof saved === 'object' && !Array.isArray(saved)) {
            for (const input of inputs) {
                if (!restoredByServer.has(input.name) && saved[input.name] === input.value) input.checked = true;
            }
        }
    } catch { /* Ignore invalid or unavailable browser drafts. */ }

    function persistDraft() {
        if (!draftKey) return;
        try {
            globalThis.sessionStorage?.setItem(draftKey, JSON.stringify(Object.fromEntries(inputs.filter(input => input.checked).map(input => [input.name, input.value]))));
        } catch { /* Storage is optional; submitting always uses server validation. */ }
    }

    function update() {
        const missing = questions.filter((question) => !question.querySelector('input:checked'));
        const answered = questions.length - missing.length;
        count.textContent = `Answered ${answered} of ${questions.length}`;
        progress.setAttribute('aria-valuenow', String(answered));
        progress.firstElementChild.style.width = `${questions.length ? answered / questions.length * 100 : 0}%`;
        unanswered.replaceChildren();
        for (const question of missing) {
            const item = root.createElement('li');
            const link = root.createElement('a');
            link.href = `#${question.id}`;
            link.textContent = `Question ${questions.indexOf(question) + 1}`;
            link.addEventListener('click', () => question.focus());
            item.append(link);
            unanswered.append(item);
        }
        if (!missing.length) unanswered.textContent = 'All questions answered.';
    }

    form.addEventListener('change', () => { update(); persistDraft(); });
    update();
    form.querySelector('[data-check-summary]').hidden = false;
}

/**
 * Score result dialogs. The dialog element gives us the modal layer, Esc,
 * the focus trap and inert background; this wires the triggers, announces
 * the result when it opens, and returns focus on close.
 */
function initializeResultDialogs(root) {
    const dialogs = root.querySelectorAll ? [...root.querySelectorAll('[data-check-result-modal]')] : [];
    if (!dialogs.length) return;

    const revealReview = (checkId) => {
        const details = root.querySelector(`[data-check-answer-review="${checkId}"]`);
        if (!details) return;

        details.open = true;
        const wrong = [...details.querySelectorAll('[data-answer-correct="false"]')];
        for (const item of wrong) item.classList.add('is-flagged');

        const target = wrong[0] || details.querySelector('summary');
        if (!target) return;
        target.setAttribute('tabindex', '-1');
        target.scrollIntoView({ block: 'center', behavior: 'smooth' });
        target.focus({ preventScroll: true });
    };

    for (const dialog of dialogs) {
        const checkId = dialog.dataset.checkResultId;
        const trigger = root.querySelector(`[data-check-result-target="check-result-${checkId}"]`);
        const closeButton = dialog.querySelector('[data-check-result-close]');
        const reviewButton = dialog.querySelector('[data-check-review-answers]');
        const retryForm = dialog.querySelector('[data-check-retry-form]');
        const announce = dialog.querySelector('.kc-result-announce');
        const announcement = announce?.textContent.trim() || '';

        const open = () => {
            if (dialog.open) return;
            dialog.showModal();
            if (announce) {
                announce.textContent = '';
                window.requestAnimationFrame(() => { announce.textContent = announcement; });
            }
        };

        const close = (returnFocus = true) => {
            if (!dialog.open) return;
            dialog.dataset.returnFocus = returnFocus ? 'true' : 'false';
            dialog.close();
        };

        trigger?.addEventListener('click', (event) => {
            event.preventDefault();
            open();
        });

        closeButton?.addEventListener('click', () => close());
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) close();
        });
        dialog.addEventListener('close', () => {
            const returnFocus = dialog.dataset.returnFocus !== 'false';
            delete dialog.dataset.returnFocus;
            if (returnFocus) trigger?.focus();
        });

        reviewButton?.addEventListener('click', () => {
            close(false);
            revealReview(checkId);
        });

        retryForm?.addEventListener('submit', () => close(false));

        if (dialog.dataset.autoOpen === 'true') {
            window.requestAnimationFrame(open);
        }
    }
}
