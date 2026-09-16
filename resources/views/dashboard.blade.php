@extends('layouts.app', ['role' => $role])

@php($displayName = auth()->user()?->username ?? 'OPERATOR')

@section('title', 'Dashboard')

@section('content')
    <header class="mb-8 flex flex-col sm:flex-row sm:items-center gap-4 border-b border-phosphor-dim/70 pb-5">
        <div>
            <h1 class="text-3xl font-display font-bold tracking-tight text-[#0a0a23]">
                Welcome back, {{ strtok($displayName, ' ') ?: $displayName }}
            </h1>
            <p class="mt-1 text-[15px] text-[#3b3b4f]">
                Pick up where you left off and keep your progress readable.
            </p>
        </div>
        <div class="sm:ml-auto flex items-center gap-3 shrink-0">
            <x-badge tone="amber">XP {{ $totalXp }}</x-badge>
            @if ($resume !== null && $resume['type'] === 'mission')
                <a href="{{ route('mission.show', $resume['mission']) }}" class="btn-primary">Continue Learning →</a>
            @else
                <a href="{{ route('learning-path') }}" class="btn-primary">Continue Learning →</a>
            @endif
        </div>
    </header>

    <div class="grid gap-6 lg:grid-cols-[2fr_1fr] items-start">
        {{-- Main column --}}
        <div class="space-y-6 min-w-0">
            <x-panel title="Next step">
                <div class="flex flex-col md:flex-row md:items-center gap-5">
                    <div class="min-w-0 flex-1">
                        @if ($course === null)
                            <p class="text-[15px] text-[#3b3b4f]">
                                Course complete. You have cleared every active course — awaiting new directives.
                            </p>
                        @elseif ($resume === null)
                            <p class="text-[15px] text-[#3b3b4f]">No pending mission in {{ $course->name }}.</p>
                        @elseif ($resume['type'] === 'mission')
                            <p class="text-xs font-bold uppercase tracking-wide text-[#6f6f79]">{{ $course?->name }}</p>
                            <h2 class="mt-1 text-xl font-display font-bold tracking-tight text-[#0a0a23]">{{ $resume['mission']->title }}</h2>
                            <p class="mt-1 text-sm text-[#3b3b4f]">
                                {{ ! empty($resume['section']) ? $resume['section']->title.' · ' : '' }}
                                {{ $resume['mission']->difficulty }}{{ $resume['mission']->points ? ' · '.$resume['mission']->points.' XP' : '' }}
                            </p>
                        @else
                            <p class="text-xs font-bold uppercase tracking-wide text-[#198eee]">Boss Challenge ready</p>
                            <h2 class="mt-1 text-xl font-display font-bold tracking-tight text-[#0a0a23]">All missions complete</h2>
                            <p class="mt-1 text-sm text-[#3b3b4f]">Complete the Boss Challenge to finish this course.</p>
                        @endif
                    </div>
                    @if ($resume !== null && $resume['type'] === 'mission')
                        <a href="{{ route('mission.show', $resume['mission']) }}" class="btn-ghost shrink-0">Resume →</a>
                    @elseif ($resume !== null && $resume['type'] === 'course')
                        <a href="{{ route('learning-path') }}" class="btn-ghost shrink-0">Continue Course →</a>
                    @endif
                </div>
            </x-panel>

            <x-panel title="Course progress">
                @if ($course === null || $courseProgress === null)
                    <x-progress-bar label="No active course" :total="100" :current="0" />
                    <p class="mt-2 text-sm text-[#6f6f79]">No active course assigned.</p>
                @else
                    <x-progress-bar
                        label="{{ $course->name }}"
                        :total="$courseProgress['total']"
                        :current="$courseProgress['completed']"
                    />
                    <p class="mt-2 text-sm text-[#6f6f79]">
                        {{ $courseProgress['completed'] }} of {{ $courseProgress['total'] }} missions complete
                    </p>
                @endif
            </x-panel>

            <x-panel title="Incoming notifications">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <p class="text-xs font-bold uppercase tracking-wide {{ $unreadCount > 0 ? 'text-[#198eee]' : 'text-[#8f8f9a]' }}">
                        {{ $unreadCount > 0 ? $unreadCount.' unread' : 'Inbox clear' }}
                    </p>
                    <a href="{{ route('notifications') }}" class="text-sm font-bold text-[#198eee] hover:text-[#1376d0]">
                        View all →
                    </a>
                </div>

                @forelse ($priorityNotifications as $notification)
                    @php($href = $notificationLinks[$notification->id] ?? null)
                    <div class="flex items-start gap-3 border-b border-[#e4e4e9] py-2.5 last:border-0 {{ $notification->read_at !== null ? 'opacity-60' : '' }}">
                        <div class="min-w-0 flex-1">
                            @if ($href !== null)
                                <a href="{{ $href }}" class="text-[15px] font-semibold text-[#0a0a23] hover:text-[#198eee]">
                                    {{ $notification->title }}
                                </a>
                            @else
                                <p class="text-[15px] font-semibold text-[#0a0a23]">{{ $notification->title }}</p>
                            @endif
                            <p class="text-sm text-[#6f6f79] mt-0.5 truncate">{{ $notification->message }}</p>
                        </div>
                        @if ($notification->read_at === null)
                            <x-badge tone="amber" class="shrink-0">NEW</x-badge>
                        @endif
                    </div>
                @empty
                    <p class="text-[15px] text-[#3b3b4f]">No priority notifications. Open the inbox for the full feed.</p>
                @endforelse
            </x-panel>
        </div>

        {{-- Side column --}}
        <aside class="space-y-6 min-w-0">
            <x-panel title="Assessment readiness">
                <x-progress-bar
                    label="Readiness"
                    :total="100"
                    :current="$course === null ? 0 : $assessmentReadiness"
                />
                <ul class="mt-4 space-y-2 text-[15px] text-[#3b3b4f]">
                    <li>Complete remaining missions</li>
                    <li>Pass the practice checkpoint</li>
                    <li>Finish the course challenge</li>
                </ul>
            </x-panel>

            <x-panel title="Recent activity">
                @forelse ($recentActivity as $activity)
                    <div class="flex items-center justify-between gap-3 border-b border-[#e4e4e9] py-2 last:border-0 text-[15px]">
                        <span class="text-[#3b3b4f] truncate">{{ $activity->message }}</span>
                        <time class="shrink-0 font-code text-xs text-[#6f6f79]">{{ $activity->created_at?->format('H:i') }}</time>
                    </div>
                @empty
                    <p class="text-[15px] text-[#3b3b4f]">No recent activity recorded.</p>
                @endforelse
            </x-panel>

            <x-panel title="Learning record">
                <dl class="space-y-2 text-sm">
                    <div class="flex items-center justify-between"><dt>Total XP</dt><dd class="font-bold text-[#0a0a23]">{{ $learnerStats['total_xp'] }}</dd></div>
                    <div class="flex items-center justify-between"><dt>Achievements earned</dt><dd class="font-bold text-[#0a0a23]">{{ $learnerStats['achievements'] }}</dd></div>
                    <div class="flex items-center justify-between"><dt>Learning streak</dt><dd class="font-bold text-[#0a0a23]">{{ $learnerStats['streak'] }}</dd></div>
                    <div class="flex items-center justify-between"><dt>Missions completed</dt><dd class="font-bold text-[#0a0a23]">{{ $learnerStats['missions_completed'] }}</dd></div>
                    <div class="flex items-center justify-between"><dt>Boss Challenges passed</dt><dd class="font-bold text-[#0a0a23]">{{ $learnerStats['boss_challenges_passed'] }}</dd></div>
                </dl>
            </x-panel>
        </aside>
    </div>
@endsection