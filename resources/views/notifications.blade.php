@extends('layouts.app', ['role' => $role, 'studentPrototype' => $role === 'student'])

@section('title', 'Notifications')

@section('content')
    @if ($role === 'student')
        <div class="mx-auto w-full max-w-[1080px]">
            <header class="flex flex-wrap items-end justify-between gap-4 border-b border-line pb-6">
                <div>
                    <p class="eyebrow">Your account</p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight text-balance md:text-3xl">Notifications</h1>
                    <p class="mt-2 max-w-[64ch] text-sm leading-6 text-fg-muted">Challenge updates, Boss Challenge results, and system announcements.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="badge badge-neutral">{{ $notifications->total() }} TOTAL</span>
                    @if ($unreadCount > 0)
                        <span class="badge badge-accent">{{ $unreadCount }} UNREAD</span>
                    @endif
                </div>
            </header>

            @if (session('notification_success'))
                <div class="panel mt-5 border-accent-line px-5 py-4" role="status">
                    <p class="text-sm font-semibold text-fg">{{ session('notification_success')['title'] }}</p>
                    <p class="mt-1 text-sm text-fg-muted">{{ session('notification_success')['message'] }}</p>
                </div>
            @endif

            @if ($notifications->isEmpty())
                <section class="panel mt-6 px-6 py-10 text-center" aria-labelledby="empty-inbox-title">
                    <h2 id="empty-inbox-title" class="text-lg font-semibold">INBOX EMPTY</h2>
                    <p class="mt-2 text-sm text-fg-muted">Your learning updates and announcements will appear here.</p>
                </section>
            @else
                <section class="mt-6" aria-labelledby="inbox-title">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                        <h2 id="inbox-title" class="text-lg font-semibold">Inbox</h2>
                        @if ($unreadCount > 0)
                            <form method="POST" action="{{ route('notifications.read-all') }}">
                                @csrf
                                <button type="submit" class="btn btn-secondary btn-sm">MARK ALL AS READ</button>
                            </form>
                        @endif
                    </div>
                    <ol class="panel divide-y divide-line">
                        @foreach ($notifications as $notification)
                            @php($href = $links[$notification->id] ?? null)
                            <li class="flex min-w-0 flex-wrap items-start gap-3 px-5 py-4 md:flex-nowrap md:px-6 {{ $notification->read_at !== null ? 'opacity-70' : '' }}">
                                <span class="mt-1 size-2 shrink-0 rounded-full {{ $notification->read_at === null ? 'bg-accent' : 'bg-line-strong' }}" aria-hidden="true"></span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if ($href !== null)
                                            <a href="{{ $href }}" class="text-sm font-semibold text-fg hover:text-accent hover:underline">{{ $notification->title }}</a>
                                        @else
                                            <p class="text-sm font-semibold text-fg">{{ $notification->title }}</p>
                                        @endif
                                        @if ($notification->type === \App\Services\NotificationService::TYPE_SYSTEM_ANNOUNCEMENT)
                                            <span class="badge badge-warning">SYSTEM</span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-sm leading-6 text-fg-muted text-pretty">{{ $notification->message }}</p>
                                    <time class="mt-2 block font-mono text-xs text-fg-subtle" datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->format('M d, H:i') }}</time>
                                </div>
                                @if ($notification->read_at === null)
                                    <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-ghost btn-sm">MARK READ</button>
                                    </form>
                                @else
                                    <span class="badge badge-neutral">READ</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                    @if ($notifications->hasPages())
                        <nav aria-label="Notification pages" class="mt-4 flex flex-wrap items-center justify-between gap-3">
                            <p class="font-mono text-xs text-fg-subtle">SHOWING PAGE {{ $notifications->currentPage() }} OF {{ $notifications->lastPage() }}</p>
                            <div class="flex items-center gap-2">
                                @if ($notifications->onFirstPage())
                                    <span class="btn btn-secondary btn-sm opacity-50" aria-disabled="true">◀ PREV</span>
                                @else
                                    <a href="{{ $notifications->previousPageUrl() }}" class="btn btn-secondary btn-sm">◀ PREV</a>
                                @endif
                                @if ($notifications->hasMorePages())
                                    <a href="{{ $notifications->nextPageUrl() }}" class="btn btn-secondary btn-sm">NEXT ▶</a>
                                @else
                                    <span class="btn btn-secondary btn-sm opacity-50" aria-disabled="true">NEXT ▶</span>
                                @endif
                            </div>
                        </nav>
                    @endif
                </section>
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
                        <span class="font-display text-[14px] leading-none {{ $notification->read_at === null ? 'text-phosphor' : 'text-phosphor-dim' }} w-4 shrink-0 text-center">▸</span>
                        <div class="min-w-0 flex-1">
                            @php($href = $links[$notification->id] ?? null)
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($href !== null)
                                    <a href="{{ $href }}" class="font-body text-[15px] text-ink hover:text-phosphor hover:underline">
                                        {{ $notification->title }}
                                    </a>
                                @else
                                    <p class="font-body text-[15px] text-ink">{{ $notification->title }}</p>
                                @endif
                                @if ($notification->type === \App\Services\NotificationService::TYPE_SYSTEM_ANNOUNCEMENT)
                                    <x-badge tone="amber">SYSTEM</x-badge>
                                @endif
                            </div>
                            <p class="font-body text-[13px] text-phosphor-dim mt-0.5">{{ $notification->message }}</p>
                        </div>
                        @if ($notification->read_at === null)
                            <form method="POST" action="{{ route('notifications.read', $notification) }}" class="shrink-0">
                                @csrf
                                <button type="submit" class="btn-ghost">MARK READ</button>
                            </form>
                        @else
                            <x-badge tone="dim" class="shrink-0">READ</x-badge>
                        @endif
                        <span class="text-xs font-bold text-phosphor-dim shrink-0 ml-auto">
                            {{ $notification->created_at->format('M d, H:i') }}
                        </span>
                    </div>
                @endforeach
            </div>

            @if ($notifications->hasPages())
                <div class="flex flex-wrap items-center justify-between gap-4 gap-y-2 p-3 border-t border-phosphor-dim/60">
                    <p class="text-sm font-bold text-phosphor-dim">
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
