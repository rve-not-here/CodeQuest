@extends('layouts.app', ['role' => $role])

@section('title', 'Announcements')

@section('content')
    <div class="admin-management">
    <header class="admin-management-context">
        <div><h1>Announcements</h1><p>Manage announcements and their audiences. Publish when the message is ready.</p></div>
        <div class="admin-actions"><a href="{{ route('admin.announcements.create') }}" class="btn-primary">+ NEW ANNOUNCEMENT</a></div>
    </header>

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

    <p class="result-count mb-3 text-sm text-ink">{{ $announcements->count() }} {{ Str::plural('announcement', $announcements->count()) }}</p>

    @if ($announcements->isEmpty())
        <x-status-message type="info" title="NO ANNOUNCEMENTS">
            No system announcements yet. Draft one to broadcast maintenance notes or learning directives.
        </x-status-message>
    @else
        <div class="admin-table-region">
            <table class="table-stack admin-table" role="table">
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

                        <tr>
                            <th scope="row" data-label="Announcement">
                                <a href="{{ route('admin.announcements.edit', $announcement) }}" class="text-phosphor hover:underline">
                                    {{ $announcement->title }}
                                </a>
                                <span class="block text-[13px] text-phosphor-dim">{{ \Illuminate\Support\Str::limit($announcement->message, 90) }}</span>
                            </th>

                            <td data-label="Audience">
                                <x-badge tone="{{ $audienceTone }}">{{ strtoupper($announcement->audience) }}</x-badge>
                            </td>

                            <td data-label="Status">
                                <x-badge tone="{{ $statusTone }}">{{ strtoupper($announcement->status) }}</x-badge>
                            </td>

                            <td data-label="Published">
                                @if ($announcement->published_at !== null)
                                    {{ $announcement->published_at->format('M d, H:i') }}
                                @else
                                    <span class="text-phosphor-dim">—</span>
                                @endif
                            </td>

                            <td data-label="Author">
                                {{ $announcement->creator?->username ?? '—' }}
                            </td>

                            <td data-label="Actions">
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
    </div>
@endsection
