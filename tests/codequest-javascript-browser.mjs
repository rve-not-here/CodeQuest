import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { join } from 'node:path';
import { pathToFileURL } from 'node:url';

const base = process.env.CODEQUEST_BROWSER_BASE_URL;
const password = process.env.CODEQUEST_DEMO_PASSWORD;
const databaseSocket = process.env.CODEQUEST_BROWSER_DATABASE_SOCKET;
const databaseName = process.env.CODEQUEST_BROWSER_DATABASE;
assert.ok(databaseSocket?.startsWith('/tmp/codequest-') && /^codequest_[a-z_]+_test$/.test(databaseName || ''), 'Database evidence requires an explicit private disposable connection.');
const fixturePath = process.env.CODEQUEST_BROWSER_JAVASCRIPT_FIXTURE;
const priorFailureEvidence = process.env.CODEQUEST_BROWSER_FAILURE_EVIDENCE;
const artifacts = process.env.CODEQUEST_BROWSER_ARTIFACTS || `/tmp/codequest-javascript-${Date.now()}`;
assert.ok(base && password && fixturePath, 'Provide a disposable application, demo password and authored curriculum fixture.');
assert.ok(['127.0.0.1', 'localhost', '[::1]'].includes(new URL(base).hostname), 'Browser mutations require a loopback disposable application.');
assert.ok(fixturePath.startsWith('/tmp/') && artifacts.startsWith('/tmp/'), 'Keep solutions and browser artifacts under /tmp.');
const missions = (await readFile(fixturePath, 'utf8')).trim().split('\n').map(line => JSON.parse(line));
const domTitles = ['DOM_Node_Activation', 'Event_Listener_Rewire', 'Query_All', 'Build_Nodes', 'Form_Guard', 'Event_Bubble', 'Input_Events', 'Form_Data'];
assert.equal(missions.filter(mission => mission.type === 'css').length, 30);
assert.equal(missions.filter(mission => mission.type === 'js').length, 35);
assert.ok(missions.every(mission => Number.isInteger(mission.id) && typeof mission.solution === 'string' && mission.solution.length));
const packagePath = process.env.CODEQUEST_PLAYWRIGHT_PATH;
const { chromium } = await import(packagePath ? pathToFileURL(join(packagePath, 'index.mjs')).href : 'playwright');
await mkdir(artifacts, { recursive: true });
const browser = await chromium.launch({ executablePath: process.env.CODEQUEST_CHROMIUM || undefined, args: ['--no-sandbox'] });
const report = { assertions: [], courses: [], failures: [], operations: [], layouts: [], dom: [], state: [], pageErrors: [], consoleErrors: [], serverErrors: [], artifacts };
if (priorFailureEvidence) {
    assert.ok(priorFailureEvidence.startsWith('/tmp/'));
    const previous = JSON.parse(await readFile(priorFailureEvidence, 'utf8'));
    assert.ok(previous.assertions.every(assertion => assertion.passed), 'Prior failure checks must have passed.');
    const cases = new Set(previous.operations.filter(operation => operation.kind === 'invalid-js').map(operation => operation.name));
    for (const name of ['syntax', 'runtime', 'missing-behavior', 'malformed', 'infinite-loop', 'long-running-timer']) {
        assert.ok(cases.has(name), `Prior browser evidence must contain ${name}.`);
        const result = previous.state.find(state => state.kind === 'failure' && state.name === name);
        assert.ok(result && result.before.progress === result.after.progress && result.before.completionRewards === result.after.completionRewards && result.after.balance >= 0 && result.after.balance <= result.before.balance, `${name} must preserve academic state.`);
    }
    for (const course of previous.courses) for (const mission of course.missions) {
        assert.ok(missions.some(authored => authored.id === mission.id && authored.title === mission.title && authored.points === mission.points), 'Prior completed missions must match this curriculum.');
    }
    report.priorFailureEvidence = priorFailureEvidence;
}
let lastSubmission = 0;
let page;
let context;
let submittedCode;

function check(name, actual, expected = true) {
    const passed = actual === expected;
    report.assertions.push({ name, passed, actual, expected });
    assert.ok(passed, `${name}: expected ${JSON.stringify(expected)}, received ${JSON.stringify(actual)}`);
}

function local(path) {
    const url = new URL(path, base);
    assert.equal(url.origin, new URL(base).origin);
    return url.href;
}

function academicState() {
    const query = `SELECT JSON_OBJECT(
        'progress', (SELECT COUNT(*) FROM the404_progress WHERE user_id=u.id),
        'completionRewards', (SELECT COUNT(*) FROM the404_xp_transactions WHERE user_id=u.id AND type IN ('mission_completed','assessment_completed')),
        'balance', (SELECT COALESCE(SUM(amount),0) FROM the404_xp_transactions WHERE user_id=u.id),
        'journal', (SELECT COUNT(*) FROM the404_mission_submission_results WHERE user_id=u.id),
        'attempts', (SELECT COUNT(*) FROM the404_assessment_attempts WHERE user_id=u.id),
        'passes', (SELECT COUNT(*) FROM the404_assessment_attempts WHERE user_id=u.id AND status='passed')
    ) FROM the404_users u WHERE username='demo.student'`;
    const output = execFileSync('mariadb', ['--no-defaults', `--socket=${databaseSocket}`, '--user=root', `--database=${databaseName}`, '--batch', '--skip-column-names', '--execute', query], { encoding: 'utf8', timeout: 5000 });
    return JSON.parse(output.trim());
}

async function navigate(path) {
    const response = await page.goto(local(path), { waitUntil: 'networkidle' });
    check(`GET ${new URL(page.url()).pathname}`, response.status(), 200);
}

async function login(role) {
    context = await browser.newContext({ viewport: { width: 1280, height: 900 }, reducedMotion: 'reduce' });
    page = await context.newPage();
    page.setDefaultTimeout(15000);
    page.on('pageerror', error => report.pageErrors.push({ url: page.url(), message: error.message }));
    page.on('console', message => {
        if (message.type() === 'error') report.consoleErrors.push({ url: page.url(), message: message.text() });
    });
    page.on('response', response => {
        if (response.status() >= 500) report.serverErrors.push({ url: response.url(), status: response.status() });
    });
    await navigate('/login');
    await page.getByLabel('Username', { exact: true }).fill(`demo.${role}`);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await Promise.all([page.waitForURL(url => url.pathname !== '/login'), page.getByRole('button', { name: 'Sign in', exact: true }).press('Enter')]);
    check(`${role} landing`, new URL(page.url()).pathname, role === 'student' ? '/dashboard' : '/students');
}

async function code(source) {
    await page.locator('.cm-content').waitFor();
    await page.evaluate(source => {
        const view = window.CodeQuest.CodeMirror.EditorView.findFromDOM(document.querySelector('.cm-editor'));
        view.dispatch({ changes: { from: 0, to: view.state.doc.length, insert: source } });
    }, source);
}

async function pace() {
    const delay = 6400 - (Date.now() - lastSubmission);
    if (delay > 0) await new Promise(resolve => setTimeout(resolve, delay));
    lastSubmission = Date.now();
}

async function submit(button) {
    await pace();
    const started = Date.now();
    const action = await button.evaluate(element => element.form.action);
    const [response, request] = await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle', timeout: 25000 }),
        page.waitForRequest(request => request.method() === 'POST' && request.url() === action),
        button.press('Enter'),
    ]);
    submittedCode = new URLSearchParams(request.postData()).get('code');
    check('Academic UI mutation responds', response.status(), 200);
    return Date.now() - started;
}

async function operation() {
    return page.locator('form.workspace-submit').evaluate(form => ({
        url: form.action,
        token: form.querySelector('[name=_token]').value,
        id: form.querySelector('[name=submission_id]').value,
    }));
}

async function replay(saved, source, expected) {
    const before = academicState();
    await pace();
    const response = await context.request.post(saved.url, {
        form: { _token: saved.token, submission_id: saved.id, code: source },
        headers: { Referer: page.url() },
    });
    check('Replay response', response.status(), 200);
    const html = await response.text();
    check('Replay preserves academic records', JSON.stringify(academicState()), JSON.stringify(before));
    report.operations.push({ kind: 'replay', url: response.url(), expected });
    check('Replay keeps authoritative result', html.includes(expected));
}

async function run(frameId = '#preview-frame', runId = '#run') {
    await page.locator(frameId).evaluate(frame => {
        frame.acceptanceLoad = new Promise(resolve => {
            const timeout = setTimeout(() => resolve(false), 15000);
            frame.addEventListener('load', () => { clearTimeout(timeout); resolve(true); }, { once: true });
        });
    });
    await page.locator(runId).focus();
    await page.locator(runId).press('Enter');
    check('Preview load is bounded', await page.locator(frameId).evaluate(frame => frame.acceptanceLoad));
    const sandbox = await page.locator(frameId).getAttribute('sandbox');
    check('Preview remains opaque with only curriculum permissions', ['allow-scripts', 'allow-scripts allow-forms'].includes(sandbox));
    if (sandbox.includes('allow-forms')) {
        check('Form preview blocks navigation through CSP', (await page.locator(frameId).getAttribute('srcdoc')).includes("form-action 'none'"));
    }
    await page.frameLocator(frameId).locator('body').waitFor({ state: 'attached' });
    return page.frameLocator(frameId);
}

async function layouts(name) {
    for (const width of [390, 768, 1280, 1920]) {
        await page.setViewportSize({ width, height: 900 });
        await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
        const state = await page.evaluate(() => ({ width: innerWidth, scrollWidth: document.documentElement.scrollWidth }));
        check(`${name} ${width}px reflows`, state.scrollWidth <= width);
        report.layouts.push({ name, ...state });
        await page.screenshot({ path: join(artifacts, `${name}-${width}.png`), fullPage: true });
    }
    await page.setViewportSize({ width: 1280, height: 900 });
}

async function knowledgeChecks() {
    const required = page.getByRole('button', { name: 'START REQUIRED CHECK', exact: false });
    const start = await required.count() ? required : page.getByRole('button', { name: 'Start check', exact: false }).first();
    if (!await start.count()) return;
    const lesson = page.url();
    await submit(start);
    const questions = page.locator('[data-check-question]');
    const total = await questions.count();
    check('Knowledge Check has authored questions', total > 0);
    for (let index = 0; index < total; index++) await questions.nth(index).getByRole('radio').first().check();
    await submit(page.getByRole('button', { name: 'Submit answers', exact: false }));
    check('Knowledge Check result persists', await page.getByRole('heading', { name: /correct$/ }).count(), 1);
    report.operations.push({ kind: 'knowledge-check', url: page.url() });
    await navigate(lesson);
}

async function previewDom(mission, frame) {
    switch (mission.title) {
        case 'DOM_Node_Activation':
            check('DOM_Node_Activation mutates authored status', await frame.locator('#status').innerText(), 'SIGNAL ACTIVE');
            break;
        case 'Event_Listener_Rewire':
            await frame.locator('#emergencyBtn').click();
            await frame.locator('#experiment-console').filter({ hasText: 'EMERGENCY PROTOCOL ACTIVATED' }).waitFor();
            check('Event_Listener_Rewire responds to real click', true);
            break;
        case 'Query_All':
            check('Query_All has authored lamps', await frame.locator('.lamp').count() > 0);
            check('Query_All changes all lamp classes', await frame.locator('.lamp:not(.on)').count(), 0);
            break;
        case 'Build_Nodes':
            check('Build_Nodes inserts authored node', await frame.locator('#bands li').filter({ hasText: 'UHF' }).count(), 1);
            break;
        case 'Form_Guard':
            await frame.locator('#callsign').fill('AB');
            await frame.locator('#signup button').click();
            await frame.locator('#experiment-console').filter({ hasText: 'TOO SHORT' }).waitFor();
            await frame.locator('#callsign').fill('CHEN');
            await frame.locator('#signup button').click();
            await frame.locator('#experiment-console').filter({ hasText: 'ACCEPTED' }).waitFor();
            check('Form_Guard rejects short and accepts valid input', true);
            break;
        case 'Event_Bubble': {
            const item = frame.locator('#bands li').first();
            const text = await item.innerText();
            await item.click();
            await frame.locator('#experiment-console').filter({ hasText: text }).waitFor();
            check('Event_Bubble observes actual clicked node', true);
            break;
        }
        case 'Input_Events':
            await frame.locator('#callsign').fill('CHEN');
            await frame.locator('#callsign').press('Tab');
            await frame.locator('#experiment-console').filter({ hasText: 'saved' }).waitFor();
            check('Input_Events input/change listeners execute', (await frame.locator('#experiment-console').innerText()).includes('CHEN'));
            break;
        case 'Form_Data':
            await frame.locator('#callsign').fill('CHEN');
            await frame.locator('#signup button').click();
            await frame.locator('#experiment-console').filter({ hasText: 'CHEN' }).waitFor();
            check('Form_Data reads actual form fields', true);
            break;
    }
}

async function javascriptFailures(mission) {
    const solution = mission.solution;
    const midpoint = Math.floor(solution.length / 2);
    const cases = [
        ['syntax', solution + '\n)'],
        ['runtime', solution + '\nthrow new Error("student runtime failure");'],
        ['missing-behavior', solution + '\nconsole.log("UNEXPECTED");'],
        ['malformed', solution.slice(0, midpoint) + '\u0000' + solution.slice(midpoint)],
        ['infinite-loop', solution + '\nwhile (true) {}'],
        ['long-running-timer', solution + '\nsetTimeout(() => console.log("late"), 60000);'],
    ];
    for (const [name, source] of cases) {
        const before = academicState();
        await code(source);
        const saved = await operation();
        const elapsed = await submit(page.getByRole('button', { name: 'Submit challenge', exact: true }));
        check(`${name} cannot complete`, await page.getByRole('heading', { name: 'Challenge complete', exact: true }).count(), 0);
        check(`${name} has student feedback`, await page.getByRole('alert').count() > 0);
        check(`${name} response is bounded`, elapsed < 25000);
        const after = academicState();
        check(`${name} creates no Progress`, after.progress, before.progress);
        check(`${name} awards no completion XP`, after.completionRewards, before.completionRewards);
        check(`${name} preserves nonnegative bounded balance`, after.balance >= 0 && after.balance <= before.balance);
        report.state.push({ kind: 'failure', name, before, after });
        report.operations.push({ kind: 'invalid-js', name, mission: mission.id, submission: saved.id, elapsed, feedback: await page.getByRole('alert').allTextContents() });
        if (name === 'syntax') await replay(saved, submittedCode, 'Challenge not passed');
        if (name === 'infinite-loop') await replay(saved, submittedCode, 'Challenge not passed');
    }
}

async function isolation() {
    const source = `const result = {}; for (const [name, read] of Object.entries({parentDOM: () => parent.document.body.innerHTML, parentCookies: () => parent.document.cookie, parentStorage: () => parent.localStorage.length, parentSession: () => parent.sessionStorage.length, parentCSRF: () => parent.document.querySelector('[name="csrf-token"]').content, topDOM: () => top.document.body.innerHTML, topNavigation: () => { top.location.href = '${base}/login#escape'; }})) { try { read(); result[name] = 'ACCESS'; } catch (error) { result[name] = error.name; } } console.log(JSON.stringify(result));`;
    const url = page.url();
    await code(source);
    const frame = await run();
    await frame.locator('#experiment-console').filter({ hasText: 'parentDOM' }).waitFor();
    const results = JSON.parse(await frame.locator('#experiment-console').innerText());
    for (const [name, value] of Object.entries(results)) check(`Preview isolation ${name}`, value, 'SecurityError');
    check('Preview cannot navigate parent', page.url(), url);
}

const bossSources = {
    css: '<style>\nbody { background: #101820; }\nh1 { color: #00ff90; font-family: monospace; }\nnav { display: flex; }\n</style>\n<h1>Emergency terminal</h1>\n<nav><a href="#signal">Signal</a><a href="#status">Status</a></nav>',
    js: '<button id="control" type="button">Toggle signal</button>\n<p id="signal">OFFLINE</p>\n<p id="ticks">5</p>\n<script>\nlet signalOn = false;\nfunction toggleSignal() {\n  signalOn = !signalOn;\n  document.getElementById("signal").textContent = signalOn ? "ONLINE" : "OFFLINE";\n  document.getElementById("ticks").textContent = "0";\n}\ndocument.getElementById("control").addEventListener("click", toggleSignal);\n</script>',
};

async function boss(course, type) {
    await navigate('/assessments');
    const row = page.locator('li[data-assessment-state]').filter({ has: page.getByRole('heading', { name: course, exact: true }) });
    const state = await row.getAttribute('data-assessment-state');
    check(`${type} Boss now eligible`, ['ready', 'in-progress', 'failed', 'passed'].includes(state));
    await navigate(await row.locator('a[href*="/assessments/"]').first().getAttribute('href'));
    if (state === 'passed') {
        check(`${type} previously accepted Boss persists`, await page.getByText('VERDICT: PASSED.', { exact: true }).count(), 1);
        report.operations.push({ kind: 'boss-already-passed', type, url: page.url() });
        return;
    }
    const start = page.getByRole('button', { name: 'INITIATE CHALLENGE', exact: true });
    if (await start.count()) await submit(start);
    if (state === 'failed') await submit(page.getByRole('button', { name: 'RETRY CHALLENGE', exact: true }));
    if (type === 'js') {
        await code('console.log("incomplete boss");');
        await submit(page.getByRole('button', { name: 'SUBMIT', exact: true }));
        check('JS Boss invalid submission persists failed verdict', await page.getByText('VERDICT: FAILED.', { exact: true }).count(), 1);
        report.operations.push({ kind: 'boss-failed', url: page.url() });
        await page.reload();
        check('JS Boss failure survives reload', await page.getByText('VERDICT: FAILED.', { exact: true }).count(), 1);
        await submit(page.getByRole('button', { name: 'RETRY CHALLENGE', exact: true }));
    }
    await code(bossSources[type]);
    const beforeBossPreview = academicState();
    await page.locator('#run').press('Enter');
    if (type === 'js') {
        check('JS Boss opaque script-only sandbox', await page.locator('#preview-frame').getAttribute('sandbox'), 'allow-scripts');
        const frame = page.frameLocator('#preview-frame');
        await frame.locator('#control').click();
        check('JS Boss preview toggles signal', await frame.locator('#signal').innerText(), 'ONLINE');
        check('JS Boss preview restarts ticks', await frame.locator('#ticks').innerText(), '0');
        check('Boss preview leaves academic state unchanged', JSON.stringify(academicState()), JSON.stringify(beforeBossPreview));
        check('Boss preview grants no pass', await page.getByText('COURSE CLEARED.', { exact: true }).count(), 0);
        await layouts('javascript-boss');
    }
    const form = await page.locator('form[action$="/submit"]').evaluate(form => ({ url: form.action, token: form.querySelector('[name=_token]').value }));
    await submit(page.getByRole('button', { name: 'SUBMIT', exact: true }));
    check(`${type} Boss server pass`, await page.getByText('VERDICT: PASSED.', { exact: true }).count(), 1);
    check(`${type} course cleared`, await page.getByText('COURSE CLEARED.', { exact: true }).count(), 1);
    report.operations.push({ kind: 'boss-passed', type, url: page.url() });
    await page.reload();
    check(`${type} Boss pass persists reload`, await page.getByText('VERDICT: PASSED.', { exact: true }).count(), 1);
    if (type === 'js') {
        const beforeBossReplay = academicState();
        await pace();
        const response = await context.request.post(form.url, { form: { _token: form.token, code: bossSources.js } });
        check('Boss completed request replay remains bounded', response.status(), 200);
        check('Boss replay leaves academic records unchanged', JSON.stringify(academicState()), JSON.stringify(beforeBossReplay));
        check('Boss replay preserves prior pass', (await response.text()).includes('COURSE CLEARED'));
        await submit(page.getByRole('button', { name: 'RETRY CHALLENGE', exact: true }));
        await code(bossSources.js);
        await submit(page.getByRole('button', { name: 'SUBMIT', exact: true }));
        check('Boss deliberate practice retry passes', await page.getByText('VERDICT: PASSED.', { exact: true }).count(), 1);
        check('Boss practice retry awards no extra reward', academicState().completionRewards, beforeBossReplay.completionRewards);
        check('Boss practice retry awards no extra balance', academicState().balance, beforeBossReplay.balance);
        check('Boss retry communicates no extra XP', (await page.locator('main').innerText()).includes('no extra XP'));
        report.operations.push({ kind: 'boss-practice-pass', url: page.url() });
    }
}

async function course(type) {
    const authored = missions.filter(mission => mission.type === type);
    const name = authored[0].course;
    await navigate('/learning-path');
    const rows = page.locator('article').filter({ has: page.getByRole('heading', { name, exact: true }) }).locator('li[data-state]');
    check(`${type} authoritative curriculum count`, await rows.count(), authored.length);
    const completed = await rows.locator('[aria-label$=", completed"]').evaluateAll(links => links.map(link => link.getAttribute('aria-label').replace(/, completed$/, '')));
    const firstRemaining = authored.find(mission => !completed.includes(mission.title));
    await navigate('/assessments');
    const assessment = page.locator('li[data-assessment-state]').filter({ has: page.getByRole('heading', { name, exact: true }) });
    if (completed.length < authored.length) check(`${type} Boss initially sealed`, await assessment.getAttribute('data-assessment-state'), 'sealed');
    const result = { name, initialCompleted: completed.length, missions: [], bossPassed: false };
    report.courses.push(result);
    for (const mission of authored) {
        if (completed.includes(mission.title)) continue;
        await navigate(`/missions/${mission.id}`);
        check(`${mission.title} lesson owns title`, (await page.locator('main').innerText()).includes(mission.title));
        if (type === 'js' && mission === firstRemaining) {
            await page.getByRole('link', { name: 'TRY IT YOURSELF', exact: false }).click();
            await code(mission.solution);
            const frame = await run('#experiment-preview', '#experiment-run');
            if (domTitles.includes(mission.title)) await previewDom(mission, frame);
            else await frame.locator('#experiment-console').filter({ hasText: /\S/ }).waitFor();
            check('JavaScript experiment executes curriculum source', true);
            check('Experiment has no grading submit form', await page.locator('form[action$="/submit"]').count(), 0);
            await navigate(`/missions/${mission.id}`);
        }
        await knowledgeChecks();
        await page.getByRole('link', { name: 'START CHALLENGE', exact: false }).click();
        if (type === 'js' && mission === firstRemaining && !priorFailureEvidence) await javascriptFailures(mission);
        await code(mission.solution);
        if (type === 'js') {
            const before = academicState();
            const errorCount = report.pageErrors.length;
            const consoleCount = report.consoleErrors.length;
            const frame = await run();
            await previewDom(mission, frame);
            check(`${mission.title} preview leaves academic state unchanged`, JSON.stringify(academicState()), JSON.stringify(before));
            if (domTitles.includes(mission.title)) {
                check(`${mission.title} valid DOM source has no page exception`, report.pageErrors.length, errorCount);
                check(`${mission.title} valid DOM source has no console error`, report.consoleErrors.length, consoleCount);
                report.dom.push({ fixture: mission.title, passed: true, pageErrors: 0, consoleErrors: 0, academicUnchanged: true });
            }
            if (mission.title === 'Form_Guard') {
                await isolation();
                const formRequests = [];
                const capture = request => { if (request.url() === local('/logout')) formRequests.push(request.url()); };
                page.on('request', capture);
                await code(`setTimeout(() => { document.querySelector('#signup').action = '${local('/logout')}'; document.querySelector('#signup').method = 'post'; document.querySelector('#signup').submit(); }, 100);`);
                await page.locator('#run').press('Enter');
                const blocked = page.frameLocator('#preview-frame');
                await blocked.locator('#signup').waitFor();
                await page.waitForTimeout(500);
                page.off('request', capture);
                check('Native preview forms cannot submit to application', formRequests.length, 0);
                check('Blocked form remains in opaque srcdoc', await blocked.locator('body').evaluate(() => location.href), 'about:srcdoc');
                check('Blocked form leaves academic state unchanged', JSON.stringify(academicState()), JSON.stringify(before));
                await code(mission.solution);
            }
            check(`${mission.title} preview creates no completion`, await page.getByRole('heading', { name: 'Challenge complete', exact: true }).count(), 0);
            if (mission.title === 'DOM_Node_Activation') {
                await layouts('javascript-dom');
                await isolation();
                await code('document.getElementById("status").textContent = "WRONG";');
                const wrong = await run();
                check('Wrong DOM is visible in preview', await wrong.locator('#status').innerText(), 'WRONG');
                await submit(page.getByRole('button', { name: 'Submit challenge', exact: true }));
                check('Wrong DOM source cannot complete', await page.getByRole('heading', { name: 'Challenge complete', exact: true }).count(), 0);
                report.operations.push({ kind: 'invalid-dom', mission: mission.id });
                await code(mission.solution);
            }
        }
        const beforeCompletion = academicState();
        const saved = await operation();
        const elapsed = await submit(page.getByRole('button', { name: 'Submit challenge', exact: true }));
        const correctSource = submittedCode;
        const afterCompletion = academicState();
        check(`${mission.title} persists one Progress`, afterCompletion.progress, beforeCompletion.progress + 1);
        check(`${mission.title} persists one reward`, afterCompletion.completionRewards, beforeCompletion.completionRewards + 1);
        check(`${mission.title} awards trusted points`, afterCompletion.balance, beforeCompletion.balance + mission.points);
        report.state.push({ kind: 'completion', mission: mission.id, before: beforeCompletion, after: afterCompletion });
        check(`${mission.title} completes server-side`, await page.getByRole('heading', { name: 'Challenge complete', exact: true }).count(), 1);
        await page.keyboard.press('Escape');
        await page.reload();
        check(`${mission.title} completion survives reload`, await page.getByText('CHALLENGE COMPLETE', { exact: true }).count(), 1);
        result.missions.push({ id: mission.id, title: mission.title, points: mission.points, submission: saved.id, elapsed });
        if (type === 'js' && mission === firstRemaining) {
            await replay(saved, correctSource, 'Already completed');
            await pace();
            const beforeDuplicates = academicState();
            const duplicates = await Promise.all([0, 1].map(() => context.request.post(saved.url, {
                form: { _token: saved.token, submission_id: saved.id, code: correctSource },
            })));
            for (const response of duplicates) {
                check('Rapid duplicate completion response', response.status(), 200);
                check('Rapid duplicate retains completed state', (await response.text()).includes('CHALLENGE COMPLETE'));
            }
            check('Rapid duplicate retains academic records', JSON.stringify(academicState()), JSON.stringify(beforeDuplicates));
            report.operations.push({ kind: 'rapid-double-submit', mission: mission.id, submission: saved.id });
        }
        process.stdout.write(`${type}: completed ${mission.title} (${result.missions.length}/${authored.length - completed.length})\n`);
    }
    await navigate('/learning-path');
    check(`${type} all missions persist completed`, await page.locator('article').filter({ has: page.getByRole('heading', { name, exact: true }) }).locator('li[data-state="completed"]').count(), authored.length);
    await boss(name, type);
    result.bossPassed = true;
}

try {
    await login('student');
    if (process.env.CODEQUEST_BROWSER_DOM_ONLY === '1') {
        for (const mission of missions.filter(mission => domTitles.includes(mission.title))) {
            await navigate(`/missions/${mission.id}`);
            await page.getByRole('link', { name: 'TRY IT YOURSELF', exact: false }).click();
            const before = academicState();
            const errors = report.pageErrors.length;
            const consoleErrors = report.consoleErrors.length;
            await code(mission.solution);
            const frame = await run('#experiment-preview', '#experiment-run');
            await previewDom(mission, frame);
            check(`${mission.title} authored DOM has no page exception`, report.pageErrors.length, errors);
            check(`${mission.title} authored DOM has no console error`, report.consoleErrors.length, consoleErrors);
            check(`${mission.title} authored preview changes no academic records`, JSON.stringify(academicState()), JSON.stringify(before));
            report.dom.push({ fixture: mission.title, passed: true, pageErrors: 0, consoleErrors: 0, academicUnchanged: true });
        }
    } else {
        await course('css');
        await course('js');
    }
    for (const path of ['/learning-path', '/xp-ledger', '/timeline', '/competency', '/achievements']) await navigate(path);
    await context.close();
    await login('teacher');
    await navigate('/students');
    const student = page.getByRole('link', { name: 'demo.student', exact: true });
    check('Teacher sees authorized demo student', await student.count(), 1);
    await student.click();
    report.teacher = { url: page.url(), text: await page.locator('main').innerText() };
    check('Teacher sees JavaScript course', report.teacher.text.includes('JavaScript'));
    await layouts('teacher-javascript');
    await navigate('/reports/teacher');
    check('Teacher cannot use curriculum administration', (await context.request.get(local('/admin/courses'))).status(), 403);
} catch (error) {
    report.failures.push(error.stack);
    process.exitCode = 1;
} finally {
    await browser.close();
    await writeFile(join(artifacts, 'report.json'), JSON.stringify(report, null, 2));
    process.stdout.write(JSON.stringify({ assertions: report.assertions.length, passed: report.assertions.filter(item => item.passed).length, failed: report.failures, courses: report.courses.map(({ name, missions, bossPassed }) => ({ name, completed: missions.length, bossPassed })), layouts: report.layouts.length, pageErrors: report.pageErrors, serverErrors: report.serverErrors, artifacts }) + '\n');
}
