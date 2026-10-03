export function initializeKnowledgeCheck(root) {
    const form = root.querySelector('[data-knowledge-check]');
    if (!form) return;
    const questions = [...form.querySelectorAll('[data-check-question]')];
    const count = form.querySelector('[data-check-count]');
    const progress = form.querySelector('[data-check-progress]');
    const unanswered = form.querySelector('[data-check-unanswered]');

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

    form.addEventListener('change', update);
    update();
    form.querySelector('[data-check-summary]').hidden = false;
}
