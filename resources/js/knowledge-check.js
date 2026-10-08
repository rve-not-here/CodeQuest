export function initializeKnowledgeCheck(root) {
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
