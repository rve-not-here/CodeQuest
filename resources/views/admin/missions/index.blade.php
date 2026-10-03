@extends('layouts.app', ['role' => $role])

@section('title', $course->name.' Missions')

@section('content')
    <x-page-header
        title="Mission Management"
        subtitle="{{ $course->name }} · Manage challenge content and learning order."
        icon="⚑"
    >
        <x-slot:actions>
            <a href="{{ route('admin.courses') }}" class="btn-ghost">◀ BACK TO COURSES</a>
            <x-badge tone="cyan">ORDERED BY ORDER_NUM</x-badge>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <x-status-message type="success" title="RECORD SAVED" class="mb-4">
            {{ session('status') }}
        </x-status-message>
    @endif

    <h2 class="text-sm font-bold text-phosphor mb-2">Missions in {{ $course->name }}</h2>

    <p class="result-count mb-3 text-sm text-ink">{{ $missions->count() }} {{ Str::plural('mission', $missions->count()) }}</p>

    @if ($missions->isEmpty())
        <x-status-message type="info" title="NO MISSIONS">
            No missions are defined for this course yet. Awaiting directives from Command.
        </x-status-message>
    @else
        <div class="panel p-0 overflow-x-auto">
            <table class="table-stack w-full text-left">
                <thead>
                    <tr class="border-b border-phosphor-dim/60 text-sm font-bold text-phosphor-dim">
                        <th class="px-4 py-3">Order</th>
                        <th class="px-4 py-3">Mission</th>
                        <th class="px-4 py-3">Difficulty</th>
                        <th class="px-4 py-3">Points</th>
                        <th class="px-4 py-3">Section</th>
                        <th class="px-4 py-3">Edit</th>
                    </tr>
                </thead>
                <tbody class="font-body text-lg">
                    @foreach ($missions as $mission)
                        <tr class="border-b border-phosphor-dim/40 align-top">
                            <td class="px-4 py-3 font-display text-[10px] text-phosphor-dim" data-label="Order">
                                {{ $mission->order_num }}
                            </td>

                            <td class="px-4 py-3" data-label="Mission">
                                <a href="{{ route('admin.courses.missions.edit', [$course, $mission]) }}" class="text-phosphor hover:underline">
                                    {{ $mission->title }}
                                </a>
                            </td>

                            <td class="px-4 py-3 font-display text-[10px]" data-label="Difficulty">
                                <x-badge tone="{{ match ($mission->difficulty) {
                                    'HARD' => 'amber',
                                    'MEDIUM' => 'cyan',
                                    default => 'dim',
                                } }}">{{ $mission->difficulty }}</x-badge>
                            </td>

                            <td class="px-4 py-3 font-display text-[10px] text-phosphor-dim" data-label="Points">
                                {{ $mission->points }}
                            </td>

                            <td class="px-4 py-3 text-[15px] text-ink truncate max-w-[220px]" data-label="Section">
                                {{ $mission->section?->title ?? '—' }}
                            </td>

                            <td class="px-4 py-3" data-label="Edit">
                                <a href="{{ route('admin.courses.missions.edit', [$course, $mission]) }}" class="text-sm font-bold text-cyan hover:underline">
                                    EDIT →
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection