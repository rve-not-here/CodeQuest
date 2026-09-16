@extends('layouts.app', ['role' => $role])

@section('title', 'Notifications')

@section('content')
    <x-page-header
        title="Notifications"
        subtitle="Your inbox. Mission completions, assessment results, and system announcements land here."
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
@endsection