@extends('layouts.app', ['role' => $role, 'studentPrototype' => $role === 'student'])

@section('title', 'Notifications')

@section('content')
    @php
        $runningFigures = $filter === 'unread'
            ? $unreadCount.' unread'
            : $totalCount.' total'.($unreadCount > 0 ? ' · '.$unreadCount.' unread' : '');
        [$inboxRoute, $inboxLabel] = match ($role) {
            'student' => [null, null],
            'admin' => [route('admin.dashboard'), 'Admin console →'],
            'teacher', 'instructor' => [null, null],
            default => [null, null],
        };
    @endphp

    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>{{ $role === 'student' ? $runningFigures : $runningFigures }}</span>
            @if ($inboxRoute !== null)
                <a href="{{ $inboxRoute }}">{{ $inboxLabel }}</a>
            @endif
        </div>

        <header class="atlas-records-heading">
            <h1 class="atlas-records-title">Notifications</h1>
            <p class="atlas-records-lede">Challenge updates, Boss Challenge results and system announcements.</p>
        </header>

        <nav class="atlas-inbox-tabs" aria-label="Inbox filters">
            <a href="{{ route('notifications') }}" @if ($filter === 'all') aria-current="page" @endif>All <span>{{ $totalCount }}</span></a>
            <a href="{{ route('notifications', ['filter' => 'unread']) }}" @if ($filter === 'unread') aria-current="page" @endif>Unread <span>{{ $unreadCount }}</span></a>
        </nav>

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
                @if ($filter === 'unread')
                    <h2 id="empty-inbox-title" class="atlas-records-empty-title">No unread notifications</h2>
                    <p class="atlas-records-empty-copy">Everything in your inbox has been read.</p>
                    <a href="{{ route('notifications') }}" class="btn btn-secondary">View all notifications <span aria-hidden="true">→</span></a>
                @else
                    <h2 id="empty-inbox-title" class="atlas-records-empty-title">Inbox empty</h2>
                    <p class="atlas-records-empty-copy">System messages and learning events will appear here.</p>
                    @if ($role === 'student')
                        <a href="{{ route('learning-path') }}" class="btn btn-secondary">Open learning path <span aria-hidden="true">→</span></a>
                    @else
                        <a href="{{ route('timeline') }}" class="btn btn-secondary">Open learning timeline <span aria-hidden="true">→</span></a>
                    @endif
                @endif
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
                        $notificationIcon = match ($notification->type) {
                            \App\Services\NotificationService::TYPE_MISSION_COMPLETED => 'check',
                            \App\Services\NotificationService::TYPE_ASSESSMENT_UNLOCKED => 'lock-open',
                            \App\Services\NotificationService::TYPE_ASSESSMENT_PASSED => 'badge-check',
                            \App\Services\NotificationService::TYPE_ASSESSMENT_FAILED => 'x',
                            \App\Services\NotificationService::TYPE_COURSE_COMPLETED => 'flag',
                            \App\Services\NotificationService::TYPE_NEXT_COURSE_UNLOCKED => 'arrow-up-right',
                            \App\Services\NotificationService::TYPE_ACHIEVEMENT_EARNED => 'award',
                            \App\Services\NotificationService::TYPE_TEACHER_ATTENTION => 'triangle-alert',
                            \App\Services\NotificationService::TYPE_SYSTEM_ANNOUNCEMENT => 'megaphone',
                            default => 'bell',
                        };
                        // The stored message repeats the title for ledger fidelity;
                        // show only the part the title has not already said.
                        $displayCopy = $notification->message;
                        foreach ([
                            $notification->title.': ',
                            $notification->title.' — ',
                            'Achievement unlocked: ',
                            'Challenge completed: ',
                            'Boss Challenge completed: ',
                            'Boss Challenge failed: ',
                            'Knowledge Check completed: ',
                        ] as $prefix) {
                            if (str_starts_with($displayCopy, $prefix)) {
                                $displayCopy = substr($displayCopy, strlen($prefix));

                                break;
                            }
                        }
                    @endphp
                    <li class="atlas-notification" data-state="{{ $isUnread ? 'unread' : 'read' }}">
                        <span class="atlas-notification-mark" aria-hidden="true"><x-icon :name="$notificationIcon" size="14" /></span>
                        <div class="atlas-notification-body">
                            <div class="atlas-notification-head">
                                @if ($href !== null)
                                    <a href="{{ $href }}" class="atlas-notification-title">{{ $notification->title }}</a>
                                @else
                                    <p class="atlas-notification-title">{{ $notification->title }}</p>
                                @endif
                                @if ($notification->type === \App\Services\NotificationService::TYPE_SYSTEM_ANNOUNCEMENT)
                                    <span class="atlas-stamp atlas-stamp--amb"><x-icon name="megaphone" size="12" />System</span>
                                @endif
                            </div>
                            <p class="atlas-notification-copy">{{ $displayCopy }}</p>
                            <p class="atlas-record-meta">
                                <time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->format('M d, Y · H:i') }}</time>
                            </p>
                        </div>
                        @if ($isUnread)
                            <div class="atlas-notification-action">
                                <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                    @csrf
                                    <button type="submit" class="atlas-row-action">Mark read</button>
                                </form>
                            </div>
                        @endif
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
