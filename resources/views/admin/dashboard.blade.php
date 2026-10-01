@extends('layouts.app', ['role' => 'admin'])

@section('title', 'Admin Console')

@section('content')
    <x-page-header
        title="Admin Console"
        subtitle="Fleet-wide administration. Every figure is computed server-side from the live learning, assessment, and audit records at request time."
    >
        <x-slot:actions>
            <x-badge tone="cyan">SYSTEM-WIDE</x-badge>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <section class="panel p-4">
            <h2 class="panel-title mb-3">Accounts</h2>
            <div class="grid grid-cols-3 gap-3">
                @foreach ([
                    'users_total' => 'Total',
                    'users_active' => 'Active',
                    'users_inactive' => 'Inactive',
                ] as $key => $label)
                    <div class="border border-phosphor-dim/40 rounded-[2px] px-3 py-2 bg-surface-alt">
                        <p class="text-xs font-bold text-phosphor-dim mb-1">{{ $label }}</p>
                        <p data-metric="{{ $key }}" class="font-display text-[26px] leading-none text-phosphor">{{ $metrics[$key] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="panel p-4">
            <h2 class="panel-title mb-3">Curriculum</h2>
            <div class="grid grid-cols-3 gap-3">
                @foreach ([
                    'courses' => 'Courses',
                    'sections' => 'Sections',
                    'challenges' => 'Challenges',
                ] as $key => $label)
                    <div class="border border-phosphor-dim/40 rounded-[2px] px-3 py-2 bg-surface-alt">
                        <p class="text-xs font-bold text-phosphor-dim mb-1">{{ $label }}</p>
                        <p data-metric="{{ $key }}" class="font-display text-[26px] leading-none text-phosphor">{{ $metrics[$key] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="panel p-4">
            <h2 class="panel-title mb-3">Assessments</h2>
            <div class="grid grid-cols-3 gap-3">
                @foreach ([
                    'boss_challenges' => 'Boss challenges',
                    'attempts' => 'Attempts',
                    'passed_attempts' => 'Passed',
                ] as $key => $label)
                    <div class="border border-phosphor-dim/40 rounded-[2px] px-3 py-2 bg-surface-alt">
                        <p class="text-xs font-bold text-phosphor-dim mb-1">{{ $label }}</p>
                        <p data-metric="{{ $key }}" class="font-display text-[26px] leading-none text-phosphor">{{ $metrics[$key] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="panel p-4">
            <h2 class="panel-title mb-3">Learning</h2>
            <div class="grid grid-cols-2 gap-3">
                @foreach ([
                    'active_students' => 'Active students (14d)',
                    'course_completions' => 'Course completions',
                ] as $key => $label)
                    <div class="border border-phosphor-dim/40 rounded-[2px] px-3 py-2 bg-surface-alt">
                        <p class="text-xs font-bold text-phosphor-dim mb-1">{{ $label }}</p>
                        <p data-metric="{{ $key }}" class="font-display text-[26px] leading-none text-phosphor">{{ $metrics[$key] }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <section class="panel p-4">
        <header class="flex items-center justify-between gap-4 mb-3 pb-2 border-b border-phosphor-dim/60">
            <h2 class="panel-title">Recent System Activity</h2>
        </header>

        @forelse ($recentSystemActivity as $entry)
            <div class="border-b border-phosphor-dim/40 py-2 last:border-0">
                <div class="flex items-baseline justify-between gap-3">
                    <span class="text-sm font-bold text-phosphor shrink-0">
                        {{ $entry->admin_username }}
                    </span>
                    <span class="text-xs font-bold text-phosphor-dim shrink-0">
                        {{ $entry->created_at->diffForHumans() }}
                    </span>
                </div>
                <p class="font-body text-[15px] text-ink mt-1">{{ $entry->summary }}</p>
            </div>
        @empty
            <p class="font-body text-[15px] text-ink">No administrative actions have been recorded yet.</p>
        @endforelse
    </section>
@endsection