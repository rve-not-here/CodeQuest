@extends('layouts.app', ['role' => $role, 'studentPrototype' => $role === 'student'])

@section('title', 'Notifications')

@section('content')
    @if ($role === 'student')
        <div class="atlas-records">
            @php
                $runningFigures = $notifications->total().' total'.($unreadCount > 0 ? ' · '.$unreadCount.' unread' : '');
            @endphp
            <div class="log-running atlas-records-running">
                <span>Records · Notifications · {{ $runningFigures }}</span>
                <a href="{{ route('learning-path') }}">Learning path →</a>
            </div>

            <header class="atlas-records-heading">
                <span class="atlas-lesson-entry-label">Records · Inbox</span>
                <h1 class="atlas-records-title">Notifications</h1>
                <p class="atlas-records-lede">Challenge updates, Boss Challenge results, and system announcements.</p>
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
                    <p class="atlas-records-empty-copy">Your learning updates and announcements will appear here.</p>
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
    @else
    <x-page-header
        title="Notifications"
        subtitle="Your inbox. Challenge completions, Boss Challenge results, and system announcements land here."
        icon="✉"
    >
        <x-slot:actions>
            <x-badge tone="phosphor">{{ $notifications->total() }} TOTAL</x-badge>
            @if ($unreadCount > 0)
                <x-badge tone="amber">{{ $unreadCount }} UNREAD</x-badge>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if (session('notification_success'))
        <x-status-message type="success" title="{{ session('notification_success')['title'] }}">
            {{ session('notification_success')['message'] }}
        </x-status-message>
    @endif

    @if ($notifications->isEmpty())
        <x-status-message type="info" title="INBOX EMPTY">
            No notifications yet. System messages and learning events will appear here.
        </x-status-message>
    @else
        @if ($unreadCount > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}" class="mb-4 text-right">
                @csrf
                <button type="submit" class="btn-ghost">MARK ALL AS READ</button>
            </form>
        @endif

        <x-panel title="INBOX">
            <div class="divide-y divide-phosphor-dim/40">
                @foreach ($notifications as $notification)
                    <div class="flex items-start gap-3 py-3 {{ $notification->read_at !== null ? 'opacity-60' : '' }}">
                        <span class="mt-[5px] inline-block w-2 h-2 shrink-0 {{ $notification->read_at === null ? 'bg-accent' : 'bg-line-strong' }}"></span>
                        <div class="min-w-0 flex-1">
                            @php($href = $links[$notification->id] ?? null)
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($href !== null)
                                    <a href="{{ $href }}" class="font-body text-[15px] text-ink hover:text-accent hover:underline">
                                        {{ $notification->title }}
                                    </a>
                                @else
                                    <p class="font-body text-[15px] text-ink">{{ $notification->title }}</p>
                                @endif
                                @if ($notification->type === \App\Services\NotificationService::TYPE_SYSTEM_ANNOUNCEMENT)
                                    <x-badge tone="amber">SYSTEM</x-badge>
                                @endif
                            </div>
                            <p class="font-body text-[13px] text-fg-muted mt-0.5">{{ $notification->message }}</p>
                        </div>
                        @if ($notification->read_at === null)
                            <form method="POST" action="{{ route('notifications.read', $notification) }}" class="shrink-0">
                                @csrf
                                <button type="submit" class="btn-ghost">MARK READ</button>
                            </form>
                        @else
                            <x-badge tone="dim" class="shrink-0">READ</x-badge>
                        @endif
                        <span class="text-xs font-bold text-fg-muted shrink-0 ml-auto">
                            {{ $notification->created_at->format('M d, H:i') }}
                        </span>
                    </div>
                @endforeach
            </div>

            @if ($notifications->hasPages())
                <div class="flex flex-wrap items-center justify-between gap-4 gap-y-2 p-3 border-t border-fg-muted/60">
                    <p class="text-sm font-bold text-fg-muted">
                        SHOWING PAGE {{ $notifications->currentPage() }} OF {{ $notifications->lastPage() }}
                    </p>
                    <div class="flex items-center gap-2">
                        @if ($notifications->onFirstPage())
                            <span class="btn-ghost opacity-50 pointer-events-none">◀ PREV</span>
                        @else
                            <a href="{{ $notifications->previousPageUrl() }}" class="btn-ghost">◀ PREV</a>
                        @endif

                        @if ($notifications->hasMorePages())
                            <a href="{{ $notifications->nextPageUrl() }}" class="btn-ghost">NEXT ▶</a>
                        @else
                            <span class="btn-ghost opacity-50 pointer-events-none">NEXT ▶</span>
                        @endif
                    </div>
                </div>
            @endif
        </x-panel>
    @endif
    @endif
@endsection
