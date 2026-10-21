@extends('layouts.app', ['role' => 'student'])

@section('title', 'Design system')

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Reference · Design system · Component sheet</span>
            <span>Examples only</span>
        </div>

        <header class="atlas-records-heading">
            <span class="atlas-lesson-entry-label">Reference · Interface</span>
            <h1 class="atlas-records-title">Design system</h1>
            <p class="atlas-records-lede">Component examples only. Values do not represent student records or eligibility.</p>
        </header>

        <section class="atlas-record-group" aria-labelledby="ds-stamps">
            <div class="atlas-record-group-head">
                <div>
                    <span class="atlas-record-entry-label">Sheet 01</span>
                    <h2 id="ds-stamps" class="atlas-record-group-title">Stamps</h2>
                </div>
            </div>
            <p class="atlas-record-group-note">Tone carries the status; the border carries the sheet.</p>
            <div class="atlas-record-entry">
                <div class="atlas-row-actions">
                    <span class="atlas-stamp atlas-stamp--ink">Neutral</span>
                    <span class="atlas-stamp atlas-stamp--vio">Students</span>
                    <span class="atlas-stamp atlas-stamp--grn">Recorded</span>
                    <span class="atlas-stamp atlas-stamp--amb">Locked</span>
                    <span class="atlas-stamp atlas-stamp--acc">Rule failed</span>
                </div>
            </div>
        </section>

        <section class="atlas-record-group" aria-labelledby="ds-actions">
            <div class="atlas-record-group-head">
                <div>
                    <span class="atlas-record-entry-label">Sheet 02</span>
                    <h2 id="ds-actions" class="atlas-record-group-title">Actions</h2>
                </div>
            </div>
            <p class="atlas-record-group-note">One ink fill per view; everything else takes the outline or a row action.</p>
            <div class="atlas-record-entry">
                <div class="atlas-record-entry-actions">
                    <button type="button" class="btn btn-primary">Continue learning →</button>
                    <button type="button" class="btn btn-secondary">Review lesson</button>
                    <button type="button" class="btn-ghost">Hint</button>
                    <div class="atlas-row-actions">
                        <button type="button" class="atlas-row-action">Edit record</button>
                        <button type="button" class="atlas-row-action atlas-row-action--danger">Archive</button>
                    </div>
                </div>
            </div>
        </section>

        <section class="atlas-record-group" aria-labelledby="ds-fields">
            <div class="atlas-record-group-head">
                <div>
                    <span class="atlas-record-entry-label">Sheet 03</span>
                    <h2 id="ds-fields" class="atlas-record-group-title">Fields</h2>
                </div>
            </div>
            <p class="atlas-record-group-note">Labels are sentence case in markup; the caps come from CSS.</p>
            <div class="atlas-form">
                <div class="atlas-form-grid">
                    <div class="atlas-form-field">
                        <label for="ds-username" class="atlas-lesson-entry-label">Username</label>
                        <input id="ds-username" class="terminal-input" value="demo.student" readonly>
                        <p class="atlas-field-help">Lowercase letters, numbers, dots and underscores.</p>
                    </div>
                    <div class="atlas-form-field">
                        <label for="ds-rejected" class="atlas-lesson-entry-label">Username (rejected)</label>
                        <input id="ds-rejected" class="terminal-input" value="demo student" aria-invalid="true" aria-describedby="ds-rejected-error" readonly>
                        <p id="ds-rejected-error" class="atlas-field-error">Use letters, numbers, dots and underscores only.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="atlas-record-group" aria-labelledby="ds-figures">
            <div class="atlas-record-group-head">
                <div>
                    <span class="atlas-record-entry-label">Sheet 04</span>
                    <h2 id="ds-figures" class="atlas-record-group-title">Figures and progress</h2>
                </div>
            </div>
            <div class="atlas-record-figures">
                <dl class="log-facts" aria-label="Example figures">
                    <div>
                        <dt>XP this week</dt>
                        <dd class="is-numeric">140</dd>
                    </div>
                    <div>
                        <dt>Challenges</dt>
                        <dd class="is-numeric">7 / 12</dd>
                    </div>
                    <div>
                        <dt>Competency</dt>
                        <dd class="is-numeric">62%</dd>
                    </div>
                </dl>
            </div>
            <div class="atlas-record-entry">
                <div>
                    <p class="atlas-path-entry-context">Course position</p>
                    <p class="atlas-path-summary-count">4 / 10 challenges complete · 40%</p>
                    <div class="progress" role="progressbar" aria-label="Example course progress" aria-valuemin="0" aria-valuemax="10" aria-valuenow="4"><span style="width: 40%"></span></div>
                </div>
            </div>
        </section>

        <section class="atlas-record-group" aria-labelledby="ds-notices">
            <div class="atlas-record-group-head">
                <div>
                    <span class="atlas-record-entry-label">Sheet 05</span>
                    <h2 id="ds-notices" class="atlas-record-group-title">Notices</h2>
                </div>
            </div>
            <div class="atlas-form">
                <div class="panel border-success/40 bg-success-soft px-4 py-3 text-sm" role="status">
                    <strong class="text-success">Submission recorded</strong>
                    <span class="text-fg-muted">The challenge result is written to your record.</span>
                </div>
                <div class="panel border-info/40 bg-info-soft px-4 py-3 text-sm" role="status">
                    <strong class="text-info">System info</strong>
                    <span class="text-fg-muted">Preview assets were rebuilt.</span>
                </div>
                <div class="panel border-warning/40 bg-warning-soft px-4 py-3 text-sm" role="status">
                    <strong class="text-warning">Degraded</strong>
                    <span class="text-fg-muted">Hints are temporarily unavailable.</span>
                </div>
                <div class="panel border-danger/40 bg-danger-soft px-4 py-3 text-sm" role="alert">
                    <strong class="text-danger">Rule failed</strong>
                    <span class="text-fg-muted">The submission did not pass validation.</span>
                </div>
            </div>
        </section>
    </div>
@endsection
