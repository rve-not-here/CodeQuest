@extends('layouts.app', ['role' => $role])

@section('title', 'Announcements')

@section('content')
    <x-page-header
        title="Announcements"
        subtitle="System announcements for maintenance, availability, and learning directives. Audience is server-determined; publish delivers the message once to every active matching user."
    >
        <x-slot:actions>
            <a href="{{ route('admin.announcements.create') }}" class="btn-ghost">+ NEW ANNOUNCEMENT</a>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <x-status-message type="success" title="RECORD SAVED" class="mb-4">
            {{ session('status') }}
        </x-status-message>
    @endif

    @if (session('error'))
        <x-status-message type="error" title="CHANGE REJECTED" class="mb-4">
            {{ session('error') }}
        </x-status-message>
    @endif

    <h2 class="text-sm font-bold text-phosphor mb-2">Draft → Published → Archived</h2>

    @if ($announcements->isEmpty())
        <x-status-message type="info" title="NO ANNOUNCEMENTS">
            No system announcements yet. Draft one to broadcast maintenance notes or learning directives.
        </x-status-message>
    @else
        <div class="panel p-0 overflow-x-auto">
            <table class="table-stack w-full text-left">
                <thead>
                    <tr class="border-b border-phosphor-dim/60 text-sm font-bold text-phosphor-dim">
                        <th class="px-4 py-3">Announcement</th>
                        <th class="px-4 py-3">Audience</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Published</th>
                        <th class="px-4 py-3">Author</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="font-body text-lg">
                    @foreach ($announcements as $announcement)
                        @php
                            $audienceTone = match ($announcement->audience) {
                                'students' => 'cyan',
                                'teachers' => 'amber',
                                'admins' => 'alert',
                                default => 'phosphor',
                            };
                            $statusTone = match ($announcement->status) {
                                'published' => 'cyan',
                                'archived' => 'amber',
                                default => 'dim',
                            };
                        @endphp

                        <tr class="border-b border-phosphor-dim/40 align-top">
                            <td class="px-4 py-3" data-label="Announcement">
                                <a href="{{ route('admin.announcements.edit', $announcement) }}" class="text-phosphor hover:underline">
                                    {{ $announcement->title }}
                                </a>
                                <span class="block text-[13px] text-phosphor-dim">{{ \Illuminate\Support\Str::limit($announcement->message, 90) }}</span>
                            </td>

                            <td class="px-4 py-3" data-label="Audience">
                                <x-badge tone="{{ $audienceTone }}">{{ strtoupper($announcement->audience) }}</x-badge>
                            </td>

                            <td class="px-4 py-3" data-label="Status">
                                <x-badge tone="{{ $statusTone }}">{{ strtoupper($announcement->status) }}</x-badge>
                            </td>

                            <td class="px-4 py-3 text-[15px] text-ink" data-label="Published">
                                @if ($announcement->published_at !== null)
                                    {{ $announcement->published_at->format('M d, H:i') }}
                                @else
                                    <span class="text-phosphor-dim">—</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-[15px] text-ink" data-label="Author">
                                {{ $announcement->creator?->username ?? '—' }}
                            </td>

                            <td class="px-4 py-3" data-label="Actions">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('admin.announcements.edit', $announcement) }}" class="text-sm font-bold text-cyan hover:underline">
                                        EDIT →
                                    </a>
                                    @if ($announcement->status === 'draft')
                                        <form method="POST" action="{{ route('admin.announcements.publish', $announcement) }}">
                                            @csrf
                                            <button type="submit" class="text-sm font-bold text-phosphor hover:underline">
                                                PUBLISH →
                                            </button>
                                        </form>
                                    @endif
                                    @if ($announcement->status === 'published')
                                        <form method="POST" action="{{ route('admin.announcements.archive', $announcement) }}">
                                            @csrf
                                            <button type="submit" class="text-sm font-bold text-alert hover:underline">
                                                ARCHIVE →
                                            </button>
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
@endsection