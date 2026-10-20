@extends('layouts.app', ['role' => $role])

@section('title', 'Edit announcement')

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Announcements · Platform scope</span>
            <a href="{{ route('admin.announcements') }}">← All announcements</a>
        </div>

        <header class="atlas-records-heading">
            <span class="atlas-lesson-entry-label">Records · Operations</span>
            <h1 class="atlas-records-title">Edit announcement</h1>
            <p class="atlas-records-lede">{{ $announcement->title }} · Changes do not resend a published announcement.</p>
            <dl class="log-facts atlas-records-facts" aria-label="Announcement record">
                <div><dt>Status</dt><dd>{{ ucfirst($announcement->status) }}</dd></div>
                <div><dt>Audience</dt><dd>{{ ucfirst($announcement->audience) }}</dd></div>
                <div><dt>Author</dt><dd>{{ $announcement->creator?->username ?? '—' }}</dd></div>
                <div>
                    <dt>Published</dt>
                    <dd class="is-numeric">{{ $announcement->published_at?->format('M d, Y · H:i') ?? 'Not published' }}</dd>
                </div>
            </dl>
        </header>

        @if (session('error'))
            <div class="atlas-records-alerts">
                <div class="panel border-danger/40 bg-danger-soft px-4 py-3 text-sm" role="alert">
                    <strong class="text-danger">Change rejected</strong>
                    <span class="text-fg-muted">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="atlas-records-alerts">
                <div class="panel border-danger/40 bg-danger-soft px-4 py-3 text-sm" role="alert">
                    <strong class="text-danger">Input rejected</strong>
                    <ul class="mt-1 list-disc pl-5">
                        @foreach ($errors->messages() as $field => $messages)
                            @foreach ($messages as $error)
                                <li><a href="#{{ Str::before($field, '.') }}" class="underline underline-offset-2">{{ $error }}</a></li>
                            @endforeach
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <section class="atlas-record-group" aria-labelledby="announcement-title">
            <div class="atlas-record-group-head">
                <h2 id="announcement-title" class="atlas-record-group-title">Announcement</h2>
            </div>

            <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}" class="atlas-form">
                @csrf
                @method('PUT')

                <div class="atlas-form-grid">
                    <div class="atlas-form-field atlas-form-field--wide">
                        <label for="title" class="atlas-lesson-entry-label">Title</label>
                        <input
                            id="title"
                            type="text"
                            name="title"
                            value="{{ old('title', $announcement->title) }}"
                            required
                            maxlength="160"
                            class="terminal-input"
                         @if ($errors->has('title')) aria-invalid="true" aria-describedby="title-error" @endif>
                        @error('title')<p id="title-error" class="atlas-field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="atlas-form-field atlas-form-field--wide">
                        <label for="message" class="atlas-lesson-entry-label">Message</label>
                        <textarea
                            id="message"
                            name="message"
                            rows="6"
                            required
                            class="terminal-input"
                         @if ($errors->has('message')) aria-invalid="true" aria-describedby="message-error" @endif>{{ old('message', $announcement->message) }}</textarea>
                        @error('message')<p id="message-error" class="atlas-field-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="atlas-form-field">
                        <label for="audience" class="atlas-lesson-entry-label">Audience</label>
                        <select id="audience" name="audience" class="terminal-input" required aria-describedby="{{ $errors->has('audience') ? 'audience-help audience-error' : 'audience-help' }}" @if ($errors->has('audience')) aria-invalid="true" @endif>
                            <option value="all" @selected(old('audience', $announcement->audience) === 'all')>All students, teachers &amp; admins</option>
                            <option value="students" @selected(old('audience', $announcement->audience) === 'students')>Students</option>
                            <option value="teachers" @selected(old('audience', $announcement->audience) === 'teachers')>Teachers</option>
                            <option value="admins" @selected(old('audience', $announcement->audience) === 'admins')>Admins</option>
                        </select>
                        @error('audience')<p id="audience-error" class="atlas-field-error">{{ $message }}</p>@enderror
                        <p id="audience-help" class="atlas-field-help">Server-determined · operators never delivered.</p>
                    </div>
                </div>

                <div class="atlas-form-actions">
                    <button type="submit" class="btn btn-primary">Save announcement →</button>
                    <a href="{{ route('admin.announcements') }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </section>

        <section class="atlas-record-group" aria-labelledby="delivery-title">
            <div class="atlas-record-group-head">
                <h2 id="delivery-title" class="atlas-record-group-title">Delivery</h2>
                <span class="atlas-stamp {{ $announcement->status === 'published' ? 'atlas-stamp--grn' : ($announcement->status === 'archived' ? 'atlas-stamp--ink' : 'atlas-stamp--amb') }}"><x-icon :name="\App\Support\StampIcon::for(ucfirst($announcement->status)) ?? 'circle'" size="12" />{{ ucfirst($announcement->status) }}</span>
            </div>

            <div class="atlas-record-entry">
                <p class="atlas-record-entry-copy">
                    @if ($announcement->status === 'draft')
                        Publishing delivers this announcement to {{ $announcement->audience === 'all' ? 'students, teachers and admins' : $announcement->audience }} and stamps the delivery time.
                    @elseif ($announcement->status === 'published')
                        Delivered {{ $announcement->published_at?->format('M d, Y · H:i') }}. Archiving retires it without touching the delivered copy.
                    @else
                        Archived — the announcement is no longer delivered and cannot be re-published.
                    @endif
                </p>

                <div class="atlas-record-entry-actions">
                    @if ($announcement->status === 'draft')
                        <form method="POST" action="{{ route('admin.announcements.publish', $announcement) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">Publish &amp; deliver →</button>
                        </form>
                    @endif

                    @if ($announcement->status === 'published')
                        <form method="POST" action="{{ route('admin.announcements.archive', $announcement) }}">
                            @csrf
                            <button type="submit" class="btn btn-secondary text-danger">Archive →</button>
                        </form>
                    @endif
                </div>
            </div>
        </section>
    </div>
@endsection
