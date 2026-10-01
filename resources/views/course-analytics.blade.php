@extends('layouts.app', ['role' => $role])

@section('title', 'Course Analytics')

@section('content')
    <x-page-header
        title="Course Analytics"
        subtitle="Per-course aggregate summaries across the fleet. Every value is derived server-side from real records."
    >
        <x-slot:actions>
            <x-badge tone="cyan">SYSTEM-WIDE</x-badge>
        </x-slot:actions>
    </x-page-header>

    @forelse ($courses as $course)
        <section class="panel p-4 mb-4">
            <header class="flex items-center justify-between gap-4 mb-3 pb-2 border-b border-phosphor-dim/60">
                <div class="flex items-center gap-2 min-w-0">
                    <h2 class="panel-title truncate">{{ strtoupper($course['course']->name) }}</h2>
                    <x-badge tone="cyan">{{ strtoupper($course['course']->type) }}</x-badge>
                </div>
                <span class="text-sm font-bold text-phosphor-dim shrink-0">
                    COURSE&nbsp;{{ $course['course']->order_num }}
                </span>
            </header>

            <div class="flex flex-wrap items-center gap-2 mb-4">
                <span class="text-sm font-bold text-phosphor">
                    STUDENTS {{ $course['engaged'] }}/{{ $course['fleet'] }}
                </span>
                <span class="inline-block text-xs font-bold border px-2 py-1 rounded-[2px] leading-none text-cyan border-cyan">
                    ✔ COMPLETED {{ $course['buckets']['completed'] }}
                </span>
                <span class="inline-block text-xs font-bold border px-2 py-1 rounded-[2px] leading-none text-phosphor border-phosphor-dim">
                    ▸ IN PROGRESS {{ $course['buckets']['in_progress'] }}
                </span>
                <span class="inline-block text-xs font-bold border px-2 py-1 rounded-[2px] leading-none text-amber border-amber">
                    ◈ READY {{ $course['buckets']['assessment_ready'] }}
                </span>
                <span class="inline-block text-xs font-bold border px-2 py-1 rounded-[2px] leading-none text-phosphor-dim border-phosphor-dim/60">
                    ○ NOT STARTED {{ $course['buckets']['not_started'] }}
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                <div class="border border-phosphor-dim/40 rounded-[2px] px-3 py-2 bg-surface-alt">
                    <p class="text-xs font-bold text-phosphor-dim mb-1">Average challenge completion</p>
                    <p class="font-display text-[16px] text-phosphor leading-none">
                        {{ $course['avg_completion'] !== null ? $course['avg_completion'].'%' : '—' }}
                    </p>
                </div>
                <div class="border border-phosphor-dim/40 rounded-[2px] px-3 py-2 bg-surface-alt">
                    <p class="text-xs font-bold text-phosphor-dim mb-1">Boss Challenge pass rate</p>
                    <p class="font-display text-[16px] text-amber leading-none">
                        {{ $course['pass_rate'] !== null ? $course['pass_rate'].'%' : '—' }}
                    </p>
                </div>
            </div>

            @if ($course['engaged'] > 0)
                @php($bands = \App\Services\CourseAnalyticsService::DISTRIBUTION_BANDS)
                <div>
                    <p class="text-xs font-bold text-phosphor-dim mb-2">
                        Completion distribution ({{ $course['engaged'] }} engaged)
                    </p>
                    <div class="space-y-1">
                        @foreach ($course['distribution'] as $index => $count)
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-phosphor-dim w-14 shrink-0">{{ $bands[$index] }}%</span>
                                <div class="flex-1 h-1.5 bg-void border border-phosphor-dim/40 rounded-[1px] overflow-hidden">
                                    <div
                                        class="h-full bg-cyan/70"
                                        style="width: {{ $course['engaged'] > 0 ? round($count / $course['engaged'] * 100) : 0 }}%"
                                    ></div>
                                </div>
                                <span class="font-display text-[9px] text-phosphor w-6 text-right shrink-0">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    @empty
        <section class="panel p-4">
            <x-status-message type="info" title="No courses">
                No active course with missions is available to summarize.
            </x-status-message>
        </section>
    @endforelse
@endsection