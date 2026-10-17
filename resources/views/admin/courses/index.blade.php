@extends('layouts.app', ['role' => $role])

@section('title', 'Course Management')

@section('content')
    <div class="admin-management">
    <x-page-header title="Course Management" subtitle="Manage course details, order, and availability. Recorded progress is preserved.">
        <x-slot:actions>
            <x-badge tone="cyan">ORDERED BY ORDER_NUM</x-badge>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <x-status-message type="success" title="RECORD SAVED" class="mb-4">
            {{ session('status') }}
        </x-status-message>
    @endif

    <x-panel title="Course Catalog">
        <x-slot:status>
            <x-badge tone="dim">{{ $courses->count() }} {{ Str::plural('course', $courses->count()) }}</x-badge>
        </x-slot:status>

        @if ($courses->isEmpty())
            <x-status-message type="info" title="NO COURSES">
                No courses are available.
            </x-status-message>
        @else
            <div class="admin-table-region">
                <table class="table-stack admin-table" role="table">
                    <caption>Course catalog</caption>
                    <thead>
                        <tr>
                            <th scope="col">Order</th>
                            <th scope="col">Course</th>
                            <th scope="col">Type</th>
                            <th scope="col">Status</th>
                            <th scope="col">Sections</th>
                            <th scope="col">Missions</th>
                            <th scope="col">Challenge</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($courses as $course)
                            @php
                                $statusTone = match ($course->status) {
                                    'active' => 'cyan',
                                    'locked' => 'amber',
                                    default => 'dim',
                                };
                            @endphp

                            <tr>
                                <td data-label="Order">
                                    {{ $course->order_num }}
                                </td>

                                <th scope="row" data-label="Course">
                                    <a href="{{ route('admin.courses.edit', $course) }}" class="text-accent hover:underline">
                                        {{ $course->name }}
                                    </a>
                                    <span class="block text-[15px] text-fg-muted">{{ $course->slug }}</span>
                                </th>

                                <td data-label="Type">
                                    {{ strtoupper($course->type) }}
                                </td>

                                <td data-label="Status">
                                    <x-badge tone="{{ $statusTone }}">{{ strtoupper($course->status) }}</x-badge>
                                </td>

                                <td data-label="Sections">
                                    <a href="{{ route('admin.courses.sections', $course) }}" class="text-sm font-bold text-info hover:underline">
                                        {{ $course->sections_count }} <x-workspace-icon name="chevron-right" class="inline-icon" />
                                    </a>
                                </td>

                                <td data-label="Missions">
                                    <a href="{{ route('admin.courses.missions', $course) }}" class="text-sm font-bold text-info hover:underline">
                                        {{ $course->missions_count }} <x-workspace-icon name="chevron-right" class="inline-icon" />
                                    </a>
                                </td>

                                <td data-label="Challenge">
                                    @if ($course->assessment_count > 0)
                                        <a href="{{ route('admin.courses.assessment', $course) }}" class="text-sm font-bold text-info hover:underline">
                                            1 <x-workspace-icon name="chevron-right" class="inline-icon" />
                                        </a>
                                    @else
                                        <span class="text-sm font-bold text-fg-muted">—</span>
                                    @endif
                                </td>

                                <td data-label="Actions">
                                    <div class="flex flex-nowrap items-center gap-3">
                                        <a href="{{ route('admin.courses.edit', $course) }}" class="admin-row-link">
                                            EDIT →
                                        </a>
                                        <form method="POST" action="{{ route('admin.courses.destroy', $course) }}" onsubmit="return confirm('Delete this course permanently? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="admin-row-link admin-row-link--danger">
                                                DELETE →
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-panel>
    </div>
@endsection
