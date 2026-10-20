@extends('layouts.app', ['role' => $role])

@section('title', 'Announcements')

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Announcements · Platform scope</span>
            <span class="atlas-stamp atlas-stamp--ink"><x-icon name="globe" size="12" />System-wide</span>
        </div>

        <header class="atlas-records-heading">
            <span class="atlas-lesson-entry-label">Records · Operations</span>
            <h1 class="atlas-records-title">Announcements</h1>
            <p class="atlas-records-lede">Manage announcements and their audiences. Lifecycle: draft → published → archived.</p>
        </header>

        @if (session('status'))
            <div class="atlas-records-alerts">
                <div class="panel border-success/40 bg-success-soft px-4 py-3 text-sm" role="status">
                    <strong class="text-success">Saved</strong>
                    <span class="text-fg-muted">{{ session('status') }}</span>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="atlas-records-alerts">
                <div class="panel border-danger/40 bg-danger-soft px-4 py-3 text-sm" role="alert">
                    <strong class="text-danger">Change rejected</strong>
                    <span class="text-fg-muted">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <section class="atlas-record-group" aria-labelledby="announcement-records-title">
            <div class="atlas-record-group-head">
                <h2 id="announcement-records-title" class="atlas-record-group-title">Announcement records</h2>
                <p class="atlas-record-group-figure">{{ $announcements->count() }} {{ Str::plural('announcement', $announcements->count()) }}</p>
            </div>

            @if ($announcements->isEmpty())
                <section class="atlas-records-empty" aria-labelledby="empty-announcements-title">
                    <h3 id="empty-announcements-title" class="atlas-records-empty-title">No announcements</h3>
                    <p class="atlas-records-empty-copy">No system announcements yet. Draft one to broadcast maintenance notes or learning directives.</p>
                    <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary">New announcement →</a>
                </section>
            @else
                <div class="atlas-records-toolbar">
                    <p class="atlas-records-subheading">Newest first</p>
                    <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary">New announcement →</a>
                </div>

                <div class="atlas-register-table-wrap">
                    <table class="table-stack atlas-register-table" role="table">
                        <caption>Announcement records</caption>
                        <thead>
                            <tr>
                                <th scope="col">Announcement</th>
                                <th scope="col">Audience</th>
                                <th scope="col">Status</th>
                                <th scope="col">Published</th>
                                <th scope="col">Author</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($announcements as $announcement)
                                @php
                                    $audienceTone = match ($announcement->audience) {
                                        'students' => 'atlas-stamp--vio',
                                        'teachers' => 'atlas-stamp--amb',
                                        'admins' => 'atlas-stamp--acc',
                                        default => 'atlas-stamp--ink',
                                    };
                                    $statusTone = match ($announcement->status) {
                                        'published' => 'atlas-stamp--grn',
                                        'archived' => 'atlas-stamp--ink',
                                        default => 'atlas-stamp--amb',
                                    };
                                @endphp

                                <tr>
                                    <th scope="row" data-label="Announcement">
                                        <a href="{{ route('admin.announcements.edit', $announcement) }}">{{ $announcement->title }}</a>
                                        <span class="atlas-register-note">{{ Str::limit($announcement->message, 90) }}</span>
                                    </th>

                                    <td data-label="Audience">
                                        <span class="atlas-stamp {{ $audienceTone }}">{{ ucfirst($announcement->audience) }}</span>
                                    </td>

                                    <td data-label="Status">
                                        <span class="atlas-stamp {{ $statusTone }}"><x-icon :name="\App\Support\StampIcon::for(ucfirst($announcement->status)) ?? 'circle'" size="12" />{{ ucfirst($announcement->status) }}</span>
                                    </td>

                                    <td data-label="Published">
                                        @if ($announcement->published_at !== null)
                                            <time datetime="{{ $announcement->published_at->toIso8601String() }}" class="atlas-register-figure whitespace-nowrap">{{ $announcement->published_at->format('M d, Y · H:i') }}</time>
                                        @else
                                            <span class="atlas-register-note">—</span>
                                        @endif
                                    </td>

                                    <td data-label="Author"><span class="whitespace-nowrap">{{ $announcement->creator?->username ?? '—' }}</span></td>

                                    <td data-label="Actions">
                                        <div class="atlas-row-actions">
                                            <a href="{{ route('admin.announcements.edit', $announcement) }}" class="atlas-row-action">Edit</a>

                                            @if ($announcement->status === 'draft')
                                                <form method="POST" action="{{ route('admin.announcements.publish', $announcement) }}">
                                                    @csrf
                                                    <button type="submit" class="atlas-row-action">Publish</button>
                                                </form>
                                            @endif

                                            @if ($announcement->status === 'published')
                                                <form method="POST" action="{{ route('admin.announcements.archive', $announcement) }}">
                                                    @csrf
                                                    <button type="submit" class="atlas-row-action atlas-row-action--danger">Archive</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
