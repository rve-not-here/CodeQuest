@extends('layouts.app', ['role' => $role, 'studentPrototype' => $role === 'student'])

@section('title', 'Notifications')

@section('content')
    @php
        $runningFigures = $notifications->total().' total'.($unreadCount > 0 ? ' · '.$unreadCount.' unread' : '');
        [$inboxRoute, $inboxLabel] = match ($role) {
            'student' => [route('learning-path'), 'Learning path →'],
            'admin' => [route('admin.dashboard'), 'Admin console →'],
            default => [route('students'), 'Students →'],
        };
    @endphp

    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Notifications · {{ $runningFigures }}</span>
            <a href="{{ $inboxRoute }}">{{ $inboxLabel }}</a>
        </div>

        <header class="atlas-records-heading">
            <span class="atlas-lesson-entry-label">Records · Inbox</span>
            <h1 class="atlas-records-title">Notifications</h1>
            <p class="atlas-records-lede">Challenge updates, Boss Challenge results and system announcements.</p>
        </header>

        @if (session('notification_success'))
            <div class="atlas-records-alerts">
                <div class="panel border-accent-line bg-accent-soft px-4 py-3 text-sm" role="status">
                    <strong class="text-accent">{{ session('notification_success')['title'] }}</strong>
                    <span class="text-fg-muted">{{ session('notification_success')['message'] }}</span>
                </div>
            </div>
        @endif

        @if ($notifications->isEmpty())
            <section class="atlas-records-empty" aria-labelledby="empty-inbox-title">
                <h2 id="empty-inbox-title" class="atlas-records-empty-title">Inbox empty</h2>
                <p class="atlas-records-empty-copy">System messages and learning events will appear here.</p>
            </section>
        @else
            <div class="atlas-records-toolbar">
                <h2 class="atlas-records-subheading">Inbox</h2>
                @if ($unreadCount > 0)
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm">Mark all as read</button>
                    </form>
                @endif
            </div>
            <ol class="atlas-inbox">
                @foreach ($notifications as $notification)
                    @php
                        $href = $links[$notification->id] ?? null;
                        $isUnread = $notification->read_at === null;
                    @endphp
                    <li class="atlas-notification" data-state="{{ $isUnread ? 'unread' : 'read' }}">
                        <span class="atlas-notification-mark" aria-hidden="true"></span>
                        <div class="atlas-notification-body">
                            <div class="atlas-notification-head">
                                @if ($href !== null)
                                    <a href="{{ $href }}" class="atlas-notification-title">{{ $notification->title }}</a>
                                @else
                                    <p class="atlas-notification-title">{{ $notification->title }}</p>
                                @endif
                                @if ($notification->type === \App\Services\NotificationService::TYPE_SYSTEM_ANNOUNCEMENT)
                                    <span class="atlas-stamp atlas-stamp--amb">System</span>
                                @endif
                            </div>
                            <p class="atlas-notification-copy">{{ $notification->message }}</p>
                            <p class="atlas-record-meta">
                                <time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->format('M d, Y · H:i') }}</time>
                            </p>
                        </div>
                        <div class="atlas-notification-action">
                            @if ($isUnread)
                                <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                    @csrf
                                    <button type="submit" class="btn-ghost">Mark read</button>
                                </form>
                            @else
                                <span class="atlas-stamp atlas-stamp--ink">Read</span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
            @if ($notifications->hasPages())
                <nav class="atlas-records-pager" aria-label="Notification pages">
                    <p class="atlas-records-pager-figure">Page {{ $notifications->currentPage() }} of {{ $notifications->lastPage() }}</p>
                    <div class="atlas-records-pager-actions">
                        @if ($notifications->onFirstPage())
                            <span class="btn btn-secondary btn-sm atlas-records-pager-off" aria-disabled="true">← Previous</span>
                        @else
                            <a href="{{ $notifications->previousPageUrl() }}" class="btn btn-secondary btn-sm">← Previous</a>
                        @endif
                        @if ($notifications->hasMorePages())
                            <a href="{{ $notifications->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next →</a>
                        @else
                            <span class="btn btn-secondary btn-sm atlas-records-pager-off" aria-disabled="true">Next →</span>
                        @endif
                    </div>
                </nav>
            @endif
        @endif
    </div>
@endsection
