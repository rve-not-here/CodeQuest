@extends('layouts.app', ['role' => $role])

@section('title', 'System Analytics')

@section('content')
    <x-page-header
        title="System Analytics"
        subtitle="Fleet-wide statistics across accounts, curriculum, learning, and the XP ledger. Every figure is computed server-side at request time; the page is strictly read-only."
        icon="Σ"
    >
        <x-slot:actions>
            <x-badge tone="cyan">SYSTEM-WIDE</x-badge>
            <x-badge tone="amber">READ-ONLY</x-badge>
        </x-slot:actions>
    </x-page-header>

    <section class="panel p-4 mb-4">
        <header class="flex items-center justify-between gap-4 mb-3 pb-2 border-b border-phosphor-dim/60">
            <h2 class="panel-title">XP Administration</h2>
            <x-badge tone="amber">VIEW ONLY</x-badge>
        </header>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
            @foreach ([
                'xp_awarded' => ['Awarded', $xp['awarded']],
                'xp_spent' => ['Spent', $xp['spent']],
                'xp_deducted' => ['Deducted', $xp['deducted']],
                'xp_outstanding' => ['Outstanding', $xp['outstanding']],
                'xp_accounts' => ['Accounts', $xp['accounts']],
            ] as $key => [$label, $value])
                <div class="border border-phosphor-dim/40 rounded-[2px] px-3 py-2 bg-surface-alt">
                    <p class="text-xs font-bold text-phosphor-dim mb-1">{{ $label }}</p>
                    <p data-metric="{{ $key }}" class="font-display text-[26px] leading-none text-phosphor">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        @forelse ($xp['by_type'] as $row)
            <table class="w-full text-left">
                <thead>
                    <tr class="text-xs font-bold text-phosphor-dim border-b border-phosphor-dim/60">
                        <th class="py-2 pr-3 font-normal">Source</th>
                        <th class="py-2 pr-3 font-normal">Direction</th>
                        <th class="py-2 pr-3 font-normal text-right">Entries</th>
                        <th class="py-2 font-normal text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b border-phosphor-dim/40 last:border-0">
                        <td class="py-2 pr-3 font-body text-[15px] text-ink">{{ $row['label'] }}</td>
                        <td class="py-2 pr-3">
                            @if ($row['direction'] === 'award')
                                <x-badge tone="phosphor">CREDIT</x-badge>
                            @elseif ($row['direction'] === 'spend')
                                <x-badge tone="cyan">SPEND</x-badge>
                            @else
                                <x-badge tone="alert">DEDUCT</x-badge>
                            @endif
                        </td>
                        <td data-metric="xp_entries_{{ $row['type'] }}" class="py-2 pr-3 font-display text-[14px] text-ink text-right">{{ $row['entries'] }}</td>
                        <td data-metric="xp_total_{{ $row['type'] }}" class="py-2 font-display text-[14px] text-phosphor text-right">{{ $row['total'] }}</td>
                    </tr>
                </tbody>
            </table>
        @empty
            <x-status-message type="info" title="Empty ledger">
                No XP transactions have been recorded yet.
            </x-status-message>
        @endforelse
    </section>

    <section class="panel p-4 mb-4">
        <h2 class="panel-title mb-3">Fleet by Role</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            @foreach ([
                'role_student' => ['Students', $roles['student'] ?? 0],
                'role_teacher' => ['Teachers', $roles['teacher'] ?? 0],
                'role_admin' => ['Admins', $roles['admin'] ?? 0],
                'role_operator' => ['Operators', $roles['operator'] ?? 0],
            ] as $key => [$label, $value])
                <div class="border border-phosphor-dim/40 rounded-[2px] px-3 py-2 bg-surface-alt">
                    <p class="text-xs font-bold text-phosphor-dim mb-1">{{ $label }}</p>
                    <p data-metric="{{ $key }}" class="font-display text-[26px] leading-none text-phosphor">{{ $value }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <section class="panel p-4">
            <h2 class="panel-title mb-3">Users</h2>
            <div class="grid grid-cols-2 gap-3">
                @foreach ([
                    'users_total' => 'Total',
                    'users_active' => 'Active',
                    'users_inactive' => 'Inactive',
                ] as $key => $label)
                    <div class="border border-phosphor-dim/40 rounded-[2px] px-3 py-2 bg-surface-alt">
                        <p class="text-xs font-bold text-phosphor-dim mb-1">{{ $label }}</p>
                        <p data-metric="{{ $key }}" class="font-display text-[26px] leading-none text-phosphor">{{ $system[$key] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="panel p-4">
            <h2 class="panel-title mb-3">Curriculum</h2>
            <div class="grid grid-cols-2 gap-3">
                @foreach ([
                    'courses' => 'Courses',
                    'sections' => 'Sections',
                    'challenges' => 'Missions',
                    'boss_challenges' => 'Assessments',
                ] as $key => $label)
                    <div class="border border-phosphor-dim/40 rounded-[2px] px-3 py-2 bg-surface-alt">
                        <p class="text-xs font-bold text-phosphor-dim mb-1">{{ $label }}</p>
                        <p data-metric="{{ $key }}" class="font-display text-[26px] leading-none text-phosphor">{{ $system[$key] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="panel p-4">
            <h2 class="panel-title mb-3">Learning</h2>
            <div class="grid grid-cols-2 gap-3">
                @foreach ([
                    'active_students' => 'Active students (14d)',
                    'completed_challenges' => 'Completed challenges',
                    'course_completions' => 'Course completions',
                    'attempts' => 'Assessment attempts',
                    'passed_attempts' => 'Attempts passed',
                    'pass_rate' => 'Assessment pass rate',
                ] as $key => $label)
                    <div class="border border-phosphor-dim/40 rounded-[2px] px-3 py-2 bg-surface-alt">
                        <p class="text-xs font-bold text-phosphor-dim mb-1">{{ $label }}</p>
                        <p data-metric="{{ $key }}" class="font-display text-[26px] leading-none text-phosphor">{{ $system[$key] !== null ? $system[$key].($key === 'pass_rate' ? '%' : '') : '-' }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <section class="panel p-4">
        <header class="flex items-center justify-between gap-4 mb-3 pb-2 border-b border-phosphor-dim/60">
            <h2 class="panel-title">Per-Course Analysis</h2>
            <x-badge tone="cyan">REUSED FROM COURSE ANALYTICS</x-badge>
        </header>

        @forelse ($courses as $row)
            <div class="border-b border-phosphor-dim/40 py-3 last:border-0">
                <div class="flex items-center gap-2 mb-2">
                    <h3 class="text-base font-bold text-phosphor truncate">{{ $row['course']->name }}</h3>
                    <span class="text-xs font-bold text-phosphor-dim">
                        STUDENTS {{ $row['engaged'] }}/{{ $row['fleet'] }}
                    </span>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-2">
                    <div class="border border-phosphor-dim/40 rounded-[2px] px-3 py-2 bg-surface-alt">
                        <p class="text-xs font-bold text-phosphor-dim mb-1">Completed</p>
                        <p data-course-metric="completed" class="font-display text-[18px] leading-none text-phosphor">{{ $row['buckets']['completed'] }}</p>
                    </div>
                    <div class="border border-phosphor-dim/40 rounded-[2px] px-3 py-2 bg-surface-alt">
                        <p class="text-xs font-bold text-phosphor-dim mb-1">In progress</p>
                        <p class="font-display text-[18px] leading-none text-phosphor">{{ $row['buckets']['in_progress'] }}</p>
                    </div>
                    <div class="border border-phosphor-dim/40 rounded-[2px] px-3 py-2 bg-surface-alt">
                        <p class="text-xs font-bold text-phosphor-dim mb-1">Ready</p>
                        <p class="font-display text-[18px] leading-none text-amber">{{ $row['buckets']['assessment_ready'] }}</p>
                    </div>
                    <div class="border border-phosphor-dim/40 rounded-[2px] px-3 py-2 bg-surface-alt">
                        <p class="text-xs font-bold text-phosphor-dim mb-1">Avg completion</p>
                        <p class="font-display text-[18px] leading-none text-ink">{{ $row['avg_completion'] !== null ? $row['avg_completion'].'%' : '-' }}</p>
                    </div>
                    <div class="border border-phosphor-dim/40 rounded-[2px] px-3 py-2 bg-surface-alt">
                        <p class="text-xs font-bold text-phosphor-dim mb-1">Pass rate</p>
                        <p class="font-display text-[18px] leading-none text-amber">{{ $row['pass_rate'] !== null ? $row['pass_rate'].'%' : '-' }}</p>
                    </div>
                </div>
            </div>
        @empty
            <x-status-message type="info" title="No courses">
                No active course with missions is available to summarize.
            </x-status-message>
        @endforelse
    </section>
@endsection