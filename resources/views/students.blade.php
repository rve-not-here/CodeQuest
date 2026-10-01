@extends('layouts.app', ['role' => $role])

@section('title', 'Teacher Dashboard')

@section('content')
    <x-page-header
        title="Teacher Dashboard"
        subtitle="System-wide summary, composed server-side from real learning and assessment data, with the student roster below."
    >
        <x-slot:actions>
            <x-badge tone="cyan">SYSTEM-WIDE</x-badge>
        </x-slot:actions>
    </x-page-header>

    @php
        $links = [
            'active_students' => route('activity'),
            'courses_in_progress' => route('course-analytics'),
            'assessments_passed' => route('course-analytics'),
            'needs_attention' => route('needs-attention'),
        ];
    @endphp

    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
        @foreach ([
            ['key' => 'total_students', 'label' => 'Total students'],
            ['key' => 'active_students', 'label' => 'Active (14d)'],
            ['key' => 'courses_in_progress', 'label' => 'Courses in progress'],
            ['key' => 'assessments_passed', 'label' => 'Assessments passed'],
            ['key' => 'needs_attention', 'label' => 'Needs attention'],
        ] as $tile)
            @php
                $tone = $tile['key'] === 'needs_attention' ? 'text-amber' : 'text-phosphor';
                $href = $links[$tile['key']] ?? null;
            @endphp
            <div class="border border-phosphor-dim/40 rounded-[2px] px-3 py-2 bg-surface-alt hover:border-phosphor-dim/80 transition-colors">
                <p class="text-xs font-bold text-phosphor-dim mb-1">{{ $tile['label'] }}</p>
                @if ($href)
                    <a href="{{ $href }}" class="font-display text-[26px] leading-none {{ $tone }} hover:underline">
                        {{ $dashboardMetrics[$tile['key']] }} →
                    </a>
                @else
                    <p class="font-display text-[26px] leading-none {{ $tone }}">{{ $dashboardMetrics[$tile['key']] }}</p>
                @endif
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 mb-4">
        <section class="panel p-4">
            <header class="flex items-center justify-between gap-4 mb-3 pb-2 border-b border-phosphor-dim/60">
                <h2 class="panel-title">Recent Activity</h2>
                <a href="{{ route('activity') }}" class="text-sm font-bold text-cyan hover:underline">
                    VIEW ALL →
                </a>
            </header>

            @forelse ($dashboardActivity as $beat)
                <div class="flex items-baseline gap-3 border-b border-phosphor-dim/40 py-1 last:border-0">
                    <span class="text-sm font-bold text-phosphor shrink-0">
                        {{ strtoupper($beat['user']['username']) }}
                    </span>
                    <span class="font-body text-[15px] text-ink truncate">{{ $beat['label'] }}</span>
                    <span class="text-xs font-bold text-phosphor-dim shrink-0">
                        {{ $beat['at']->diffForHumans() }}
                    </span>
                </div>
            @empty
                <p class="font-body text-[15px] text-ink">No learning activity in the last 14 days.</p>
            @endforelse
        </section>

        <section class="panel p-4">
            <header class="flex items-center justify-between gap-4 mb-3 pb-2 border-b border-phosphor-dim/60">
                <h2 class="panel-title">Assessment Summary</h2>
                <a href="{{ route('course-analytics') }}" class="text-sm font-bold text-cyan hover:underline">
                    FULL ANALYTICS →
                </a>
            </header>

            @forelse ($dashboardSummary as $row)
                <div class="border-b border-phosphor-dim/40 py-2 last:border-0">
                    <div class="flex items-baseline justify-between gap-3">
                        <span class="text-sm font-bold text-phosphor truncate">
                            {{ strtoupper($row['course']->name) }}
                        </span>
                        <span class="text-xs font-bold text-phosphor-dim shrink-0">
                            PASS RATE {{ $row['pass_rate'] !== null ? $row['pass_rate'].'%' : '—' }}
                        </span>
                    </div>
                    <p class="font-body text-[13px] text-ink mt-1">
                        ✔ {{ $row['buckets']['completed'] }} · ▸ {{ $row['buckets']['in_progress'] }} ·
                        ◈ {{ $row['buckets']['assessment_ready'] }} · ○ {{ $row['buckets']['not_started'] }}
                    </p>
                </div>
            @empty
                <p class="font-body text-[15px] text-ink">No active course with missions to summarize.</p>
            @endforelse
        </section>
    </div>

    <h2 class="text-sm font-bold text-phosphor mb-2">Student Roster</h2>

    <form method="GET" action="{{ route('students') }}" class="panel p-4 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            <div>
                <label for="q" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Search
                </label>
                <input
                    id="q"
                    name="q"
                    type="search"
                    value="{{ $filters['q'] ?? '' }}"
                    placeholder="USERNAME / NAME"
                    class="terminal-input"
                >
            </div>

            <div>
                <label for="course" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Current course
                </label>
                <select id="course" name="course" class="terminal-input">
                    <option value="">ALL COURSES</option>
                    @foreach ($filterCourses as $course)
                        <option
                            value="{{ $course->id }}"
                            @selected(($filters['course'] ?? null) == $course->id)
                        >{{ $course->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="status" class="block text-sm font-bold text-phosphor-dim mb-1">
                    Progress
                </label>
                <select id="status" name="status" class="terminal-input">
                    <option value="">ANY</option>
                    <option value="in_progress" @selected(($filters['status'] ?? null) === 'in_progress')>IN PROGRESS</option>
                    <option value="ready" @selected(($filters['status'] ?? null) === 'ready')>READY</option>
                    <option value="completed" @selected(($filters['status'] ?? null) === 'completed')>ALL CLEARED</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="btn-ghost">FILTER →</button>
                @if (! empty($filters['q']) || ! empty($filters['course']) || ! empty($filters['status']))
                    <a href="{{ route('students') }}" class="btn-ghost">CLEAR</a>
                @endif
            </div>
        </div>

        @if ($errors->any())
            <x-status-message type="error" title="QUERY REJECTED" class="mt-3">
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </x-status-message>
        @endif
    </form>

    @if ($students->isEmpty())
        <x-status-message type="info" title="NO TRAINEES">
            No trainees match the current scan parameters.
        </x-status-message>
    @else
        <div class="panel p-0 overflow-x-auto">
            <table class="table-stack w-full text-left">
                <thead>
                    <tr class="border-b border-phosphor-dim/60 text-sm font-bold text-phosphor-dim">
                        <th class="px-4 py-3">Trainee</th>
                        <th class="px-4 py-3">Course</th>
                        <th class="px-4 py-3">Progress</th>
                        <th class="px-4 py-3">Assessment</th>
                        <th class="px-4 py-3">Competency</th>
                        <th class="px-4 py-3">Last activity</th>
                        <th class="px-4 py-3">ATTN</th>
                    </tr>
                </thead>
                <tbody class="font-body text-lg">
                    @foreach ($students as $row)
                        @php
                            $stateLabel = strtoupper(str_replace('_', ' ', $row['state']));
                            $courseTone = match ($row['state']) {
                                'ready' => 'amber',
                                'completed' => 'phosphor',
                                default => 'cyan',
                            };
                            $assessmentTone = match ($row['assessment']) {
                                'ALL CLEARED' => 'phosphor',
                                'READY' => 'amber',
                                default => 'dim',
                            };
                            $counts = $row['competency'];
                            $competencyParts = [];
                            if ($counts['demonstrated'] > 0) {
                                $competencyParts[] = 'DEMONSTRATED '.$counts['demonstrated'];
                            }
                            if ($counts['practicing'] > 0) {
                                $competencyParts[] = 'PRACTICING '.$counts['practicing'];
                            }
                            if ($counts['developing'] > 0) {
                                $competencyParts[] = 'DEVELOPING '.$counts['developing'];
                            }
                            if ($counts['not_started'] > 0) {
                                $competencyParts[] = 'STARTING '.$counts['not_started'];
                            }
                            $competencyLabel = $competencyParts === [] ? '—' : implode(' · ', $competencyParts);
                        @endphp

                        <tr class="border-b border-phosphor-dim/40 align-top">
                            <td class="px-4 py-3" data-label="Trainee">
                                <a
                                    href="{{ route('student-progress', ['student' => $row['id']]) }}"
                                    class="text-phosphor hover:underline"
                                >{{ $row['username'] }}</a>
                                @if ($row['name'])
                                    <span class="block text-[15px] text-phosphor-dim">{{ $row['name'] }}</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-ink" data-label="Course">{{ $row['currentCourse']['name'] ?? '—' }}</td>

                            <td class="px-4 py-3" data-label="Progress">
                                @if ($row['progress'] !== null)
                                    <x-badge tone="{{ $courseTone }}">
                                        {{ $stateLabel }} {{ $row['progress']['completed'] }}/{{ $row['progress']['total'] }} · {{ $row['progress']['percent'] }}%
                                    </x-badge>
                                @else
                                    <span class="text-xs font-bold text-phosphor-dim">ALL CLEARED</span>
                                @endif
                            </td>

                            <td class="px-4 py-3" data-label="Assessment">
                                <x-badge tone="{{ $assessmentTone }}">{{ $row['assessment'] }}</x-badge>
                            </td>

                            <td class="px-4 py-3 text-[15px] text-ink" data-label="Competency">{{ $competencyLabel }}</td>

                            <td class="px-4 py-3 text-[15px]" data-label="Last activity">
                                @if ($row['lastActivity'] !== null)
                                    <span class="block text-ink">{{ $row['lastActivity']['message'] }}</span>
                                    <span class="block text-[13px] text-phosphor-dim">{{ $row['lastActivity']['at'] }}</span>
                                @else
                                    <span class="text-phosphor-dim">—</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-phosphor-dim" data-label="Attn">—</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($students->hasPages())
                <div class="flex flex-wrap items-center justify-between gap-4 gap-y-2 p-3 border-t border-phosphor-dim/60">
                    <p class="text-sm font-bold text-phosphor-dim">
                        SHOWING PAGE {{ $students->currentPage() }} OF {{ $students->lastPage() }}
                    </p>
                    <div class="flex items-center gap-2">
                        @if ($students->onFirstPage())
                            <span class="btn-ghost opacity-50 pointer-events-none">◀ PREV</span>
                        @else
                            <a href="{{ $students->previousPageUrl() }}" class="btn-ghost">◀ PREV</a>
                        @endif

                        @if ($students->hasMorePages())
                            <a href="{{ $students->nextPageUrl() }}" class="btn-ghost">NEXT ▶</a>
                        @else
                            <span class="btn-ghost opacity-50 pointer-events-none">NEXT ▶</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @endif
@endsection