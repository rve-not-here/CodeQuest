import assert from 'node:assert/strict';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { join } from 'node:path';
import { pathToFileURL } from 'node:url';

const base = process.env.CODEQUEST_BROWSER_BASE_URL;
const password = process.env.CODEQUEST_DEMO_PASSWORD;
assert.ok(base && password, 'Set CODEQUEST_BROWSER_BASE_URL and CODEQUEST_DEMO_PASSWORD for a seeded disposable application.');
assert.ok(['127.0.0.1', 'localhost', '[::1]'].includes(new URL(base).hostname), 'Browser mutations require a loopback disposable application.');
const packagePath = process.env.CODEQUEST_PLAYWRIGHT_PATH;
const { chromium } = await import(packagePath ? pathToFileURL(join(packagePath, 'index.mjs')).href : 'playwright');
const artifacts = process.env.CODEQUEST_BROWSER_ARTIFACTS || `/tmp/codequest-browser-${Date.now()}`;
assert.ok(artifacts.startsWith('/tmp/'), 'Keep browser artifacts under /tmp.');
await mkdir(artifacts, { recursive: true });
const report = { assertions: [], layouts: [], screenshots: [], pageErrors: [], serverErrors: [], consoleErrors: [], blocked: [], failures: [], artifacts };
const browser = await chromium.launch({ executablePath: process.env.CODEQUEST_CHROMIUM || undefined, args: ['--no-sandbox'] });
let activeRole;

if (process.env.CODEQUEST_BROWSER_SHELL_ONLY === '1') {
    await inspectShells();
    await browser.close();
    await writeFile(join(artifacts, 'shell-report.json'), JSON.stringify(report, null, 2));
    console.log(JSON.stringify(report));
    process.exit(report.failures.length ? 1 : 0);
}

if (process.env.CODEQUEST_BROWSER_PATH_ONLY === '1') {
    try {
        await inspectLearningPath();
    } catch (error) {
        report.failures.push(error.message);
    } finally {
        await browser.close();
    }
    report.failures.push(...report.pageErrors.map(error => error.message), ...report.serverErrors.map(error => `HTTP ${error.status}`));
    await writeFile(join(artifacts, 'path-report.json'), JSON.stringify(report, null, 2));
    console.log(JSON.stringify({ assertions: report.assertions.length, failures: report.failures, screenshots: report.screenshots.length }));
    process.exit(report.failures.length ? 1 : 0);
}

const widths = [390, 768, 1280, 1920];

function check(name, actual, expected = true) {
    const passed = actual === expected;
    report.assertions.push({ role: activeRole, name, passed, actual, expected });
    if (!passed) report.failures.push(`${activeRole}: ${name}`);
}

function local(url) {
    const target = new URL(url, base);
    assert.equal(target.origin, new URL(base).origin, 'A discovered link must stay on the disposable application.');
    return target.href;
}

async function navigate(page, path) {
    const response = await page.goto(local(path), { waitUntil: 'networkidle' });
    check(`GET ${new URL(page.url()).pathname}`, response.status(), 200);
    assert.equal(response.status(), 200, 'The requested browser page must render successfully.');
    return response;
}

async function screenshot(page, screen, width) {
    const path = join(artifacts, `${activeRole}-${screen}-${width}.png`);
    await page.screenshot({ path, fullPage: true });
    report.screenshots.push(path);
}

async function inspectLayout(page, screen) {
    for (const width of widths) {
        await page.setViewportSize({ width, height: 900 });
        await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
        const layout = await page.evaluate(() => {
            const recoveredTableOverflow = [];
            const previousFocus = document.activeElement;
            const clippedControls = [...document.querySelectorAll('a, button, input, select, summary')].filter(element => {
                const box = element.getBoundingClientRect();
                const style = getComputedStyle(element);
                if (!box.width || !box.height || style.visibility === 'hidden' || element.closest('[inert]') || (box.left >= -2 && box.right <= innerWidth + 2)) return false;
                if (element.closest('table')) {
                    let container = element.parentElement;
                    while (container && !(['auto', 'scroll'].includes(getComputedStyle(container).overflowX) && container.scrollWidth > container.clientWidth)) container = container.parentElement;
                    if (container) {
                        const originalScroll = container.scrollLeft;
                        element.focus();
                        const focusedBox = element.getBoundingClientRect();
                        const containerBox = container.getBoundingClientRect();
                        const recovered = focusedBox.left >= Math.max(0, containerBox.left) - 2 && focusedBox.right <= Math.min(innerWidth, containerBox.right) + 2;
                        container.scrollLeft = originalScroll;
                        if (recovered) {
                            recoveredTableOverflow.push({ name: element.getAttribute('aria-label') || element.textContent.trim(), recoveredByKeyboardFocus: true });
                            return false;
                        }
                    }
                }
                return true;
            }).map(element => ({ tag: element.tagName, name: element.getAttribute('aria-label') || element.textContent.trim().slice(0, 80) }));
            previousFocus?.focus({ preventScroll: true });
            return { width: innerWidth, scrollWidth: document.documentElement.scrollWidth, clippedControls, recoveredTableOverflow };
        });
        report.layouts.push({ role: activeRole, screen, ...layout });
        check(`${screen} ${width}px no page overflow`, layout.scrollWidth <= width);
        check(`${screen} ${width}px controls remain in viewport`, layout.clippedControls.length, 0);
        await screenshot(page, screen, width);
    }
    await page.setViewportSize({ width: 390, height: 900 });
}

async function login(role) {
    activeRole = role;
    const context = await browser.newContext({ viewport: { width: 390, height: 900 }, reducedMotion: 'reduce' });
    const page = await context.newPage();
    page.on('dialog', dialog => dialog.accept());
    page.setDefaultTimeout(12000);
    page.on('pageerror', error => {
        if (!report.pageErrors.some(item => item.role === role && item.url === page.url() && item.message === error.message)) {
            report.pageErrors.push({ role, url: page.url(), message: error.message });
        }
    });
    page.on('console', message => {
        if (message.type() === 'error') report.consoleErrors.push({ role, url: page.url(), message: message.text() });
    });
    page.on('response', response => {
        if (response.status() >= 500) report.serverErrors.push({ role, url: response.url(), status: response.status() });
    });
    await navigate(page, '/login');
    await page.getByLabel('Username', { exact: true }).fill(`demo.${role}`);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await Promise.all([page.waitForURL(url => url.pathname !== '/login'), page.getByRole('button', { name: 'Sign in', exact: true }).press('Enter')]);
    check('role landing page', new URL(page.url()).pathname, { student: '/dashboard', teacher: '/students', admin: '/admin', operator: '/notifications' }[role]);
    return { context, page };
}

async function denied(context, path) {
    const response = await context.request.get(local(path));
    check(`denied ${path}`, response.status(), 403);
}

async function keyboardNavigation(page, student = false) {
    await page.setViewportSize({ width: 390, height: 900 });
    if (student) {
        const summary = page.getByLabel('Open account menu', { exact: true });
        await summary.focus();
        await summary.press('Enter');
        check('keyboard opens account', await page.locator('.cq-account-menu').evaluate(element => element.open));
        await page.keyboard.press('Tab');
        check('Tab enters account links', await page.evaluate(() => document.activeElement.closest('.cq-account-menu') !== null && document.activeElement.tagName !== 'SUMMARY'));
        await page.keyboard.press('Escape');
        check('Escape closes account', await page.locator('.cq-account-menu').evaluate(element => !element.open));
        check('Escape restores summary focus', await summary.evaluate(element => element === document.activeElement));
        await summary.press('Enter');
        await page.mouse.click(8, 100);
        check('outside click closes account', await page.locator('.cq-account-menu').evaluate(element => !element.open));
    } else {
        const trigger = page.getByRole('button', { name: 'Open navigation menu' });
        await trigger.focus();
        await trigger.press('Enter');
        await page.waitForFunction(() => document.activeElement?.closest('#cq-sidebar'));
        check('drawer opens by keyboard', await trigger.getAttribute('aria-expanded'), 'true');
        await page.keyboard.press('Escape');
        check('Escape closes drawer', await trigger.getAttribute('aria-expanded'), 'false');
        check('drawer restores trigger focus', await trigger.evaluate(element => element === document.activeElement));
        check('mobile logout has accessible name', await page.getByRole('button', { name: 'Sign out', exact: true }).count(), 1);
    }
}

async function logout(context, page, student = false) {
    await page.setViewportSize({ width: 390, height: 900 });
    if (student) await page.getByLabel('Open account menu', { exact: true }).press('Enter');
    await Promise.all([page.waitForURL('**/login'), page.getByRole('button', { name: 'Sign out', exact: true }).press('Enter')]);
    check('logout returns to login', new URL(page.url()).pathname, '/login');
    const response = await context.request.get(local('/notifications'));
    check('logged-out session cannot reopen private page', new URL(response.url()).pathname, '/login');
}

async function editorCode(page, code) {
    await page.locator('.cm-content').waitFor();
    await page.evaluate(source => {
        const view = window.CodeQuest.CodeMirror.EditorView.findFromDOM(document.querySelector('.cm-editor'));
        view.dispatch({ changes: { from: 0, to: view.state.doc.length, insert: source } });
    }, code);
}

async function completeHtmlCourse(page) {
    const fixturePath = process.env.CODEQUEST_BROWSER_HTML_FIXTURE;
    if (!fixturePath) return;
    assert.ok(fixturePath.startsWith('/tmp/'), 'Opt-in authored solutions must come from a disposable fixture under /tmp.');
    const fixture = JSON.parse(await readFile(fixturePath, 'utf8'));
    assert.equal(fixture.course, 'HTML Fundamentals', 'This optional journey covers the seeded HTML course only.');
    assert.ok(fixture.missions && Object.values(fixture.missions).every(source => typeof source === 'string' && source.length), 'Every fixture solution must contain authored source.');
    await navigate(page, '/learning-path');
    const htmlCourse = () => page.locator('article').filter({ has: page.getByRole('heading', { name: fixture.course, exact: true }) });
    const missions = await htmlCourse().locator('li[data-state]').evaluateAll(rows => rows.map(row => {
        const anchor = row.querySelector('a[href]');
        return {
            title: row.querySelector('h4')?.textContent.trim(),
            href: anchor?.href,
            completed: row.dataset.state === 'completed',
        };
    }));
    assert.equal(missions.length, Object.keys(fixture.missions).length, 'Public learning path must match the complete authored fixture.');
    assert.ok(missions.every(mission => fixture.missions[mission.title] && mission.href), 'Each HTML mission must have an authorized lesson link and fixture.');
    await navigate(page, '/assessments');
    const htmlBoss = () => page.locator('li[data-assessment-state]').filter({ has: page.getByRole('heading', { name: fixture.course, exact: true }) });
    report.htmlCourse = { course: fixture.course, total: missions.length, initialCompleted: missions.filter(mission => mission.completed).length, initialBossState: await htmlBoss().getAttribute('data-assessment-state'), completions: [], bossPassed: false, nextCourseAccessible: false };
    const remaining = missions.filter(mission => !mission.completed);
    if (remaining.length) {
        check('HTML Boss sealed while authored missions remain', report.htmlCourse.initialBossState, 'sealed');
        check('HTML Boss cannot be started before all missions', await htmlBoss().locator('a[href*="/assessments/"]').count(), 0);
        // Academic submissions share a 12/minute limit with Knowledge Checks.
        // Allow the preceding check requests to expire, then stay below that limit.
        process.stdout.write(`HTML journey: ${remaining.length}/${missions.length} missions remain; respecting the academic submission window.\n`);
        await new Promise(resolve => setTimeout(resolve, 60000));
    }
    let lastSubmission = 0;
    async function submit(button) {
        const delay = 6400 - (Date.now() - lastSubmission);
        if (delay > 0) await new Promise(resolve => setTimeout(resolve, delay));
        const [response] = await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' }), button.press('Enter')]);
        lastSubmission = Date.now();
        if (response.status() === 429) {
            const retryAfter = Number(await response.headerValue('retry-after'));
            report.htmlCourse.retryAfter = Number.isFinite(retryAfter) ? retryAfter : null;
            throw new Error(`Academic rate limit reached; retry only after Retry-After ${retryAfter} seconds.`);
        }
        assert.equal(response.status(), 200, 'Academic UI mutation must return a successful page.');
    }
    for (const [index, mission] of remaining.entries()) {
        await navigate(page, mission.href);
        await page.getByRole('link', { name: 'START CHALLENGE', exact: false }).click();
        await editorCode(page, fixture.missions[mission.title]);
        await submit(page.getByRole('button', { name: 'Submit challenge', exact: true }));
        const completion = await page.getByRole('heading', { name: 'Challenge complete', exact: true }).count();
        check(`authored HTML mission completes: ${mission.title}`, completion, 1);
        assert.equal(completion, 1, `Authored solution failed for ${mission.title}: ${await page.locator('main').innerText()}`);
        await page.keyboard.press('Escape');
        report.htmlCourse.completions.push(mission.title);
        process.stdout.write(`HTML journey: completed ${mission.title} (${index + 1}/${remaining.length}).\n`);
        if (index === remaining.length - 2) {
            await navigate(page, '/assessments');
            check('HTML Boss stays sealed until the final mission completes', await htmlBoss().getAttribute('data-assessment-state'), 'sealed');
        }
    }
    await navigate(page, '/learning-path');
    check('all HTML missions recorded complete', await htmlCourse().locator('li[data-state="completed"]').count(), missions.length);
    await navigate(page, '/assessments');
    const bossState = await htmlBoss().getAttribute('data-assessment-state');
    check('HTML Boss becomes available only after completed prerequisites', ['ready', 'in-progress', 'passed'].includes(bossState));
    const bossLink = await htmlBoss().locator('a[href*="/assessments/"]').first().getAttribute('href');
    await navigate(page, bossLink);
    if (bossState === 'ready') await submit(page.getByRole('button', { name: 'INITIATE CHALLENGE', exact: true }));
    if (bossState !== 'passed') {
        const instructions = await page.locator('#boss-briefing-title').locator('..').locator('..').innerText();
        assert.ok(instructions.includes('/safe') && instructions.includes('/register'), 'Solve the actual visible HTML Boss brief.');
        const source = '<h1>Emergency Broadcast</h1>\n<a href="/safe">Return to Safety</a>\n<form action="/register"><label>Callsign <input name="callsign"></label><button type="submit">Register</button></form>';
        await editorCode(page, source);
        await page.getByRole('button', { name: 'RUN', exact: true }).press('Enter');
        check('HTML Boss preview uses submitted source', await page.locator('#preview-frame').getAttribute('srcdoc'), source);
        check('HTML Boss preview keeps opaque sandbox', await page.locator('#preview-frame').getAttribute('sandbox'), '');
        await inspectLayout(page, 'boss-workspace');
        await submit(page.getByRole('button', { name: 'SUBMIT', exact: true }));
    }
    check('real HTML Boss receives passing server verdict', await page.getByText('VERDICT: PASSED.', { exact: true }).count(), 1);
    check('real HTML course shows cleared confirmation', await page.getByText('COURSE CLEARED.', { exact: true }).count(), 1);
    report.htmlCourse.bossPassed = await page.getByText('VERDICT: PASSED.', { exact: true }).count() === 1;
    await page.locator('.cm-content').waitFor();
    const previewHeading = page.frameLocator('#preview-frame').getByRole('heading', { name: 'Emergency Broadcast', exact: true });
    await previewHeading.waitFor({ state: 'visible' });
    check('HTML Boss result renders saved source in isolated preview', await previewHeading.count(), 1);
    const preview = page.locator('#preview-frame');
    report.htmlCourse.savedPreview = {
        matchesSavedSource: await preview.getAttribute('srcdoc') === await page.locator('#editor-source').inputValue(),
        frame: await preview.evaluate(element => ({ title: element.title, sandbox: element.getAttribute('sandbox'), bounds: element.getBoundingClientRect().toJSON(), viewportHeight: innerHeight, background: getComputedStyle(element).backgroundColor })),
        document: await page.frameLocator('#preview-frame').locator('body').evaluate(element => ({ readyState: document.readyState, text: element.innerText, background: getComputedStyle(element).backgroundColor, headingColor: getComputedStyle(element.querySelector('h1')).color, headingBounds: element.querySelector('h1').getBoundingClientRect().toJSON() })),
    };
    check('HTML Boss result preview reflects server-saved source', report.htmlCourse.savedPreview.matchesSavedSource);
    await inspectLayout(page, 'boss-result');
    await navigate(page, '/assessments');
    check('HTML Boss pass persists in authoritative assessment list', await htmlBoss().getAttribute('data-assessment-state'), 'passed');
    await navigate(page, '/learning-path');
    const cssCourse = page.locator('article').filter({ has: page.getByRole('heading', { name: 'CSS Styling', exact: true }) });
    const cssLesson = await cssCourse.locator('li[data-state] a[href*="/missions/"]').first().getAttribute('href');
    assert.ok(cssLesson, 'The next CSS course must expose a real authorized lesson.');
    await navigate(page, cssLesson);
    check('next CSS course lesson is reachable after HTML Boss pass', await page.getByRole('link', { name: 'START CHALLENGE', exact: false }).count(), 1);
    report.htmlCourse.nextCourseAccessible = true;
    await inspectLayout(page, 'next-course-lesson');
}

let unrelatedStudent;
let ownStudent;
let unrelatedClassroom;

try {
    const { context, page } = await login('admin');
    try {
        await keyboardNavigation(page);
        await inspectLayout(page, 'home');
        await navigate(page, '/admin/users');
        for (const username of ['demo.student', 'demo.unrelated.student']) {
            const href = await page.getByRole('link', { name: username, exact: true }).getAttribute('href');
            const id = new URL(href, base).pathname.match(/\/users\/(\d+)$/)?.[1];
            assert.ok(id, 'Discover the seeded student through authorized admin UI.');
            if (username === 'demo.student') ownStudent = id;
            else unrelatedStudent = id;
        }
        await inspectLayout(page, 'users');
        await navigate(page, '/admin/classrooms');
        const unrelated = await page.getByRole('link', { name: 'Development classroom DEMO-02', exact: true }).getAttribute('href');
        unrelatedClassroom = new URL(unrelated, base).pathname.match(/\/classrooms\/(\d+)\/edit$/)?.[1];
        assert.ok(unrelatedClassroom, 'Discover unrelated classroom from authorized admin UI.');
        await inspectLayout(page, 'classrooms');
        for (const path of ['/admin/courses', '/admin/system', '/reports/admin/system']) await navigate(page, path);
        for (const path of ['/dashboard', '/assessments', '/competency']) await denied(context, path);
        await logout(context, page);
    } catch (error) {
        report.failures.push(`admin journey: ${error.message}`);
        await screenshot(page, 'failure', 390);
    } finally { await context.close(); }

    const student = await login('student');
    try {
        const { context, page } = student;
        await keyboardNavigation(page, true);
        await inspectLayout(page, 'home');
        await navigate(page, '/learning-path');
        const missionLinks = await page.locator('main a[href]').evaluateAll(elements => [...new Set(elements.map(element => element.href).filter(href => /\/missions\/\d+$/.test(href)))]);
        assert.ok(missionLinks.length, 'Learning path must offer a lesson.');
        const completedHeading = page.getByRole('link', { name: 'Header_Reconstruction, completed', exact: true });
        const lesson = await completedHeading.count() ? await completedHeading.getAttribute('href') : missionLinks[0];
        await inspectLayout(page, 'learning-path');
        await navigate(page, '/assessments');
        check('Boss remains sealed before course prerequisites', await page.locator('[data-assessment-state="sealed"]').count() > 0);
        check('no premature Boss start form', await page.locator('form[action$="/start"]').count(), 0);

        let knowledgeLesson;
        for (const href of missionLinks) {
            const response = await context.request.get(local(href));
            if ((await response.text()).includes('/knowledge-checks/')) { knowledgeLesson = href; break; }
        }
        if (knowledgeLesson) {
            await navigate(page, knowledgeLesson);
            const startCheck = page.getByRole('button', { name: 'Start check', exact: false }).first();
            if (await startCheck.count()) {
                await Promise.all([page.waitForURL('**/knowledge-checks/**/attempts/**'), startCheck.press('Enter')]);
            } else {
                await page.locator('main a[href*="/knowledge-checks/"][href*="/attempts/"]').first().click();
            }
            await inspectLayout(page, 'knowledge-check');
            const questions = page.locator('[data-check-question]');
            const count = await questions.count();
            if (count) {
                for (let i = 0; i < count; i++) await questions.nth(i).getByRole('radio').first().check();
                check('Knowledge Check announces answered count', await page.locator('[data-check-count]').innerText(), `Answered ${count} of ${count}`);
                await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' }), page.getByRole('button', { name: 'Submit answers', exact: false }).press('Enter')]);
            }
            check('Knowledge Check result saved', await page.getByRole('heading', { name: /correct$/ }).count(), 1);
            const completedAttempt = page.url();
            await Promise.all([page.waitForURL(url => /\/knowledge-checks\/\d+\/attempts\/\d+$/.test(url.pathname) && url.href !== completedAttempt), page.getByRole('button', { name: 'Retry check', exact: true }).press('Enter')]);
            const unsentAttempt = page.url();
            const selectedOption = page.locator('[data-check-question]').first().getByRole('radio').first();
            const selectedId = await selectedOption.getAttribute('id');
            await selectedOption.check();
            const storageKey = await page.locator('[data-knowledge-check]').getAttribute('data-draft-key');
            report.knowledgeCheckDraft = { attempt: unsentAttempt, storageKey, selectedId };
            check('Knowledge Check storage key uses user and attempt', storageKey, `cq:check:${ownStudent}:${new URL(unsentAttempt).pathname.match(/attempts\/(\d+)$/)[1]}`);
            check('Knowledge Check selection stored in session', await page.evaluate(key => sessionStorage.getItem(key) !== null, storageKey));
            await page.getByRole('link', { name: /Return to .* lesson/ }).click();
            await page.getByRole('link', { name: 'Resume check', exact: false }).first().click();
            await page.waitForFunction(() => document.querySelector('[data-check-summary]')?.hidden === false);
            check('Knowledge Check resumes the same unsent attempt', page.url(), unsentAttempt);
            check('Knowledge Check return preserves an unsent selection', await page.locator(`[id="${selectedId}"]`).isChecked());
            const resumedQuestions = page.locator('[data-check-question]');
            for (let i = 0; i < await resumedQuestions.count(); i++) await resumedQuestions.nth(i).getByRole('radio').first().check();
            await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' }), page.getByRole('button', { name: 'Submit answers', exact: false }).press('Enter')]);
            check('Knowledge Check submission clears draft selections', await page.evaluate(key => sessionStorage.getItem(key) === null, storageKey));
            await page.getByRole('link', { name: /Return to .* lesson/ }).click();
            check('Knowledge Check return restores lesson context', page.url(), local(knowledgeLesson));
        } else report.blocked.push('No reachable Knowledge Check exists in seeded current course.');

        await navigate(page, lesson);
        await inspectLayout(page, 'lesson');
        await page.getByRole('link', { name: 'TRY IT YOURSELF', exact: false }).click();
        await page.locator('.cm-content').waitFor();
        await page.getByRole('button', { name: 'RUN / PREVIEW', exact: true }).press('Enter');
        check('experiment stays ungraded', await page.locator('form[action*="/missions/"]').count(), 0);
        check('experiment preview has isolated origin', await page.locator('#experiment-preview').getAttribute('sandbox'), '');
        await page.getByRole('link', { name: 'RETURN TO LESSON', exact: false }).click();
        await page.getByRole('link', { name: 'START CHALLENGE', exact: false }).click();
        await page.locator('.cm-content').waitFor();
        await inspectLayout(page, 'challenge');
        await editorCode(page, '<h1>EMERGENCY BROADCAST</h1>');
        await page.getByRole('button', { name: 'Run', exact: true }).press('Enter');
        check('preview reflects editor source', await page.locator('#preview-frame').getAttribute('srcdoc'), '<h1>EMERGENCY BROADCAST</h1>');
        await page.locator('[data-pane-toggle="editor"]').press('Enter');
        const saveDraft = page.getByRole('button', { name: 'Save draft', exact: true });
        if (await saveDraft.count()) {
            await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' }), saveDraft.press('Enter')]);
            check('draft feedback visible', await page.getByText('DRAFT SAVED', { exact: true }).count(), 1);
            await page.reload({ waitUntil: 'networkidle' });
            await page.locator('.cm-content').waitFor();
            check('saved draft survives refresh', await page.locator('.cm-content').innerText(), '<h1>EMERGENCY BROADCAST</h1>');
        } else report.blocked.push('First mission was already completed: fresh draft persistence was not exercised on this run.');
        await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' }), page.getByRole('button', { name: 'Submit challenge', exact: true }).press('Enter')]);
        if (await page.locator('[data-completion-overlay]').count()) {
            check('server-confirmed completion dialog', await page.getByRole('heading', { name: 'Challenge complete', exact: true }).count(), 1);
            await page.keyboard.press('Escape');
            check('completion dialog closes by keyboard', await page.locator('[data-completion-overlay]').count(), 0);
            await navigate(page, '/learning-path');
            check('completed mission reflected in path', await page.locator('[data-state="completed"]').count() > 0);
        } else if (await page.getByText('Already completed', { exact: true }).count()) {
            check('duplicate completion shows server feedback', true);
        } else {
            check('failed submission exposes feedback', await page.getByRole('alert').count() > 0);
            report.blocked.push('First mission did not complete; inspect grader/configuration feedback. Full course/Boss success was not exercised.');
        }
        await completeHtmlCourse(page);
        for (const path of ['/progress', '/competency', '/reports/progress']) await navigate(page, path);
        for (const path of ['/students', '/admin', '/classrooms']) await denied(context, path);
        await logout(context, page, true);
    } catch (error) {
        report.failures.push(`student journey: ${error.message}`);
        await screenshot(student.page, 'failure', 390);
    } finally { await student.context.close(); }

    const teacher = await login('teacher');
    try {
        const { context, page } = teacher;
        await keyboardNavigation(page);
        await inspectLayout(page, 'home');
        check('authorized student visible', await page.getByText('demo.student', { exact: true }).count() > 0);
        check('unrelated student omitted', await page.getByText('demo.unrelated.student', { exact: true }).count(), 0);
        await navigate(page, '/classrooms');
        await inspectLayout(page, 'classrooms');
        check('only own classroom offered', await page.getByText('Development classroom DEMO-02', { exact: true }).count(), 0);
        const classroomHref = await page.getByRole('link', { name: 'Development classroom DEMO-01', exact: true }).getAttribute('href');
        await navigate(page, classroomHref);
        assert.ok(ownStudent && unrelatedStudent && unrelatedClassroom, 'Admin discovery must provide real IDs for scope checks.');
        await navigate(page, `/student-progress/${ownStudent}`);
        if (report.htmlCourse?.bossPassed) {
            const htmlRow = page.locator('tr').filter({ hasText: 'HTML Fundamentals' }).filter({ hasText: 'PASSED' });
            check('teacher monitoring reflects real HTML Boss pass', await htmlRow.count() > 0);
        }
        await page.getByRole('link', { name: 'VIEW STUDENT REPORT', exact: false }).click();
        check('teacher student report opens', new URL(page.url()).pathname, `/reports/teacher/students/${ownStudent}`);
        for (const path of ['/course-analytics', '/needs-attention', '/reports/teacher']) await navigate(page, path);
        for (const path of [`/student-progress/${unrelatedStudent}`, `/classrooms/${unrelatedClassroom}`, '/admin', '/dashboard']) await denied(context, path);
        await logout(context, page);
    } catch (error) {
        report.failures.push(`teacher journey: ${error.message}`);
        await screenshot(teacher.page, 'failure', 390);
    } finally { await teacher.context.close(); }

    const operator = await login('operator');
    try {
        const { context, page } = operator;
        await keyboardNavigation(page);
        for (const path of ['/dashboard', '/students', '/admin', '/assessments', '/competency', '/classrooms']) await denied(context, path);
        await logout(context, page);
    } catch (error) {
        report.failures.push(`operator journey: ${error.message}`);
        await screenshot(operator.page, 'failure', 390);
    } finally { await operator.context.close(); }
} catch (error) {
    report.failures.push(`browser setup: ${error.message}`);
} finally {
    await browser.close();
    report.failures.push(...report.pageErrors.map(error => `${error.role}: browser exception ${error.message}`));
    report.failures.push(...report.serverErrors.map(error => `${error.role}: HTTP ${error.status} ${error.url}`));
    report.passed = report.assertions.filter(item => item.passed).length;
    report.assertionsFailed = report.assertions.filter(item => !item.passed).length;
    report.failed = report.failures.length;
    if (!report.htmlCourse?.bossPassed || !report.htmlCourse?.nextCourseAccessible) report.blocked.push('Full course completion, Boss pass and next-course unlock were not verified on this browser run. Set CODEQUEST_BROWSER_HTML_FIXTURE to an authored HTML fixture under /tmp for the opt-in full course journey.');
    report.result = report.failures.length ? 'failed' : 'passed';
    const output = JSON.stringify(report, null, 2).split(password).join('[REDACTED]');
    await writeFile(join(artifacts, 'report.json'), output);
    process.stdout.write(`${output}\n`);
    if (report.failures.length) process.exitCode = 1;
}

async function inspectShells() {
    for (const role of ['student', 'teacher', 'admin']) {
        activeRole = role;
        const context = await browser.newContext({ reducedMotion: 'reduce' });
        await context.route('**/*', (route) => {
            const request = route.request();
            return request.resourceType() === 'font' || new URL(request.url()).origin !== new URL(base).origin
                ? route.abort() : route.continue();
        });
        const page = await context.newPage();
        const errors = [];
        page.on('pageerror', (error) => errors.push(error.message));
        await page.goto(local('/login'));
        await page.locator('[name="username"]').fill(`demo.${role}`);
        await page.locator('[name="password"]').fill(password);
        await page.getByRole('button', { name: 'Sign in', exact: true }).click();
        await page.waitForLoadState('networkidle');
        const home = page.url();
        await context.setOffline(true);
        for (const [width, height] of [[1920, 1080], [1366, 768], [768, 1024], [390, 844]]) {
            await page.setViewportSize({ width, height });
            const label = `${role}-${width}`;
            const layout = await page.evaluate(() => ({
                width: innerWidth,
                scrollWidth: document.documentElement.scrollWidth,
                canvas: getComputedStyle(document.body).backgroundColor,
                mainCount: document.querySelectorAll('main').length,
            }));
            check(`${label} offline shell works`, await page.evaluate(() => !navigator.onLine));
            check(`${label} no page overflow`, layout.scrollWidth <= width);
            check(`${label} canvas`, layout.canvas, 'rgb(17, 20, 22)');
            check(`${label} main landmark`, layout.mainCount, 1);
            await page.screenshot({ path: join(artifacts, `${label}.png`), fullPage: true });
            const account = page.locator('[data-account-disclosure]');
            await account.locator('summary').focus();
            await page.keyboard.press('Enter');
            check(`${label} keyboard account opens`, await account.evaluate((node) => node.open));
            await page.screenshot({ path: join(artifacts, `${label}-account.png`), fullPage: true });
            await page.keyboard.press('Escape');
            check(`${label} account closes`, await account.evaluate((node) => !node.open));
            check(`${label} account restores focus`, await account.locator('summary').evaluate((node) => node === document.activeElement));
            await account.locator('summary').click();
            await page.locator('main').click({ position: { x: 4, y: 4 } });
            check(`${label} outside click closes account`, await account.evaluate((node) => !node.open));
            const navigation = page.locator('[data-navigation-disclosure]');
            if (width < 1200) {
                await navigation.locator('summary').focus();
                await page.keyboard.press('Space');
                check(`${label} keyboard navigation opens`, await navigation.evaluate((node) => node.open));
                await page.screenshot({ path: join(artifacts, `${label}-navigation.png`), fullPage: true });
                await page.keyboard.press('Tab');
                check(`${label} Tab reaches navigation link`, await navigation.locator('a').first().evaluate((node) => node === document.activeElement));
                await page.keyboard.press('Escape');
                check(`${label} navigation returns focus`, await navigation.locator('summary').evaluate((node) => node === document.activeElement));
                check(`${label} navigation closes`, await navigation.evaluate((node) => !node.open));
            } else {
                check(`${label} primary navigation visible`, await page.getByRole('navigation', { name: 'Primary', exact: true }).isVisible());
                const secondary = page.locator('.atlas-secondary');
                await secondary.locator('summary').focus();
                await page.keyboard.press('Enter');
                check(`${label} secondary disclosure opens`, await secondary.evaluate((node) => node.open));
                await page.screenshot({ path: join(artifacts, `${label}-secondary.png`), fullPage: true });
                await page.keyboard.press('Tab');
                check(`${label} secondary links keyboard accessible`, await secondary.locator('a').first().evaluate((node) => node === document.activeElement));
                await page.keyboard.press('Escape');
                check(`${label} secondary closes`, await secondary.evaluate((node) => !node.open));
                check(`${label} secondary focus restored`, await secondary.locator('summary').evaluate((node) => node === document.activeElement));
            }
            const shellColors = await page.locator('[data-atlas-shell]').evaluate((node) => {
                const text = node.querySelector('summary');
                return { background: getComputedStyle(node).backgroundColor, foreground: getComputedStyle(text).color };
            });
            const luminance = (rgb) => {
                const channels = rgb.match(/[\d.]+/g).slice(0, 3).map((value) => {
                    const channel = Number(value) / 255;
                    return channel <= 0.04045 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4;
                });
                return channels[0] * 0.2126 + channels[1] * 0.7152 + channels[2] * 0.0722;
            };
            const contrast = (luminance(shellColors.foreground) + 0.05) / (luminance(shellColors.background) + 0.05);
            check(`${label} shell text contrast`, contrast >= 4.5);
            report.layouts.push({ role, width, shellTextContrast: contrast });
            const controls = await page.locator('[data-atlas-shell] a, [data-atlas-shell] summary').evaluateAll((nodes) => nodes.filter((node) => node.getClientRects().length).map((node) => ({ label: node.textContent.trim(), height: node.getBoundingClientRect().height })));
            check(`${label} shell controls reach 44px`, controls.every((control) => control.height >= 44));
            report.layouts.push({ role, width, height, ...layout });
        }
        await context.setOffline(false);
        await page.setViewportSize({ width: 390, height: 844 });
        await page.reload();
        await page.waitForLoadState('networkidle');
        await context.setOffline(true);
        await page.locator('[data-navigation-disclosure] summary').click();
        check(`${role} offline navigation works`, await page.locator('[data-navigation-disclosure]').evaluate((node) => node.open));
        await page.screenshot({ path: join(artifacts, `${role}-offline.png`), fullPage: true });
        await page.keyboard.press('Escape');
        const session = await context.newCDPSession(page);
        await session.send('DOM.enable');
        await session.send('CSS.enable');
        const { root } = await session.send('DOM.getDocument');
        const { nodeId } = await session.send('DOM.querySelector', { nodeId: root.nodeId, selector: '.atlas-brand' });
        const { fonts } = await session.send('CSS.getPlatformFontsForNode', { nodeId });
        check(`${role} local font fallback rendered`, fonts.length > 0 && fonts.every((font) => !font.isCustomFont));
        report.layouts.push({ role, fonts });
        await context.setOffline(false);
        await page.setViewportSize({ width: 320, height: 768 });
        check(`${role} 320px shell fits`, await page.locator('[data-atlas-shell]').evaluate((node) => node.scrollWidth <= innerWidth));
        await session.send('Emulation.setDeviceMetricsOverride', { width: 683, height: 384, deviceScaleFactor: 2, mobile: false });
        check(`${role} 200 percent equivalent reflow`, await page.locator('[data-atlas-shell]').evaluate((node) => node.scrollWidth <= innerWidth));
        await page.screenshot({ path: join(artifacts, `${role}-200-percent-reflow.png`), fullPage: true });
        await session.send('Emulation.clearDeviceMetricsOverride');
        await page.goto(home);
        await page.keyboard.press('Tab');
        check(`${role} skip link first`, await page.locator('.atlas-skip').evaluate((node) => node === document.activeElement));
        await page.keyboard.press('Enter');
        check(`${role} skip link reaches main`, await page.locator('main').evaluate((node) => node === document.activeElement));
        check(`${role} no page exceptions`, errors.length, 0);
        await context.close();
    }
}

async function inspectLearningPath() {
    const { context, page } = await login('student');
    try {
        await navigate(page, '/learning-path');
        const courses = page.locator('main article');
        const missions = page.locator('main li[data-domain-state]');
        check('catalog has missions', await missions.count() > 0);
        check('sections are not hidden in accordions', await page.locator('main details[id^="section-"]').count(), 0);
        check('one Boss endpoint per course', await page.locator('main section[aria-labelledby^="boss-"]').count(), await courses.count());
        check('locked mission links absent', await page.locator('li[data-state="locked"] a').count(), 0);
        check('current mission exists', await page.locator('li[aria-current="step"]').count(), 1);
        for (const [width, height] of [[1920, 1080], [1366, 768], [768, 1024], [390, 844], [683, 768], [341, 768], [320, 844]]) {
            await page.setViewportSize({ width, height });
            check(`${width}px no page overflow`, await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
            check(`${width}px every mission visible`, await missions.locator('h4:visible').count(), await missions.count());
            const current = page.locator('li[aria-current="step"] a');
            await current.focus();
            check(`${width}px primary focus visible`, await current.evaluate(node => node === document.activeElement && getComputedStyle(node).outlineStyle !== 'none'));
            check(`${width}px primary touch target`, (await current.boundingBox()).height >= 44);
            await page.keyboard.press('Tab');
            check(`${width}px Tab reaches following mission`, await page.evaluate(() => document.activeElement.matches('li[data-domain-state] a')));
            await page.evaluate(() => scrollTo(0, 0));
            await screenshot(page, 'learning-path', width);
        }
        await page.locator('li[aria-current="step"] a').press('Enter');
        await page.waitForLoadState('networkidle');
        check('Continue opens the lesson', new URL(page.url()).pathname.startsWith('/missions/'));
        await navigate(page, '/learning-path');
        const textBefore = await page.locator('main').innerText();
        await page.evaluate(() => { for (const sheet of document.styleSheets) sheet.disabled = true; });
        check('CSS-free current label readable', (await page.locator('main').innerText()).includes('CURRENT'));
        check('CSS-free locked reason readable', (await page.locator('main').innerText()).includes('Pass earlier courses before opening this course.'));
        check('CSS-free course content preserved', (await page.locator('main').innerText()).replace(/\s+/g, ' ').trim(), textBefore.replace(/\s+/g, ' ').trim());
    } finally {
        await context.close();
    }
}
