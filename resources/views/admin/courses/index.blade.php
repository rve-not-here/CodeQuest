@extends('layouts.app', ['role' => $role])

@section('title', 'Course Management')

@section('content')
    <x-page-header
        title="Course Management"
        subtitle="The course catalog, ordered by order_num. Status is an access gate: locking or drafting a course seals its missions and challenge but never rewrites recorded progress."
    >
        <x-slot:actions>
            <x-badge tone="cyan">ORDERED BY ORDER_NUM</x-badge>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <x-status-message type="success" title="RECORD SAVED" class="mb-4">
            {{ session('status') }}
        </x-status-message>
    @endif

    <h2 class="text-sm font-bold text-phosphor mb-2">Course Catalog</h2>

    @if ($courses->isEmpty())
        <x-status-message type="info" title="NO COURSES">
            No course directives are loaded. Awaiting new directives from Command.
        </x-status-message>
    @else
        <div class="panel p-0 overflow-x-auto">
            <table class="table-stack w-full text-left">
                <thead>
                    <tr class="border-b border-phosphor-dim/60 text-sm font-bold text-phosphor-dim">
                        <th class="px-4 py-3">Order</th>
                        <th class="px-4 py-3">Course</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Sections</th>
                        <th class="px-4 py-3">Missions</th>
                        <th class="px-4 py-3">Challenge</th>
                        <th class="px-4 py-3">Edit</th>
                    </tr>
                </thead>
                <tbody class="font-body text-lg">
                    @foreach ($courses as $course)
                        @php
                            $statusTone = match ($course->status) {
                                'active' => 'cyan',
                                'locked' => 'amber',
                                default => 'dim',
                            };
                        @endphp

                        <tr class="border-b border-phosphor-dim/40 align-top">
                            <td class="px-4 py-3 font-display text-[10px] text-phosphor-dim" data-label="Order">
                                {{ $course->order_num }}
                            </td>

                            <td class="px-4 py-3" data-label="Course">
                                <a href="{{ route('admin.courses.edit', $course) }}" class="text-phosphor hover:underline">
                                    {{ $course->name }}
                                </a>
                                <span class="block text-[15px] text-phosphor-dim">{{ $course->slug }}</span>
                            </td>

                            <td class="px-4 py-3 text-[15px] text-ink" data-label="Type">
                                {{ strtoupper($course->type) }}
                            </td>

                            <td class="px-4 py-3" data-label="Status">
                                <x-badge tone="{{ $statusTone }}">{{ strtoupper($course->status) }}</x-badge>
                            </td>

                            <td class="px-4 py-3" data-label="Sections">
                                <a href="{{ route('admin.courses.sections', $course) }}" class="text-sm font-bold text-cyan hover:underline">
                                    {{ $course->sections_count }} ▸
                                </a>
                            </td>

                            <td class="px-4 py-3" data-label="Missions">
                                <a href="{{ route('admin.courses.missions', $course) }}" class="text-sm font-bold text-cyan hover:underline">
                                    {{ $course->missions_count }} ▸
                                </a>
                            </td>

                            <td class="px-4 py-3" data-label="Challenge">
                                @if ($course->assessment_count > 0)
                                    <a href="{{ route('admin.courses.assessment', $course) }}" class="text-sm font-bold text-cyan hover:underline">
                                        1 ▸
                                    </a>
                                @else
                                    <span class="text-sm font-bold text-phosphor-dim">—</span>
                                @endif
                            </td>

                            <td class="px-4 py-3" data-label="Edit">
                                <a href="{{ route('admin.courses.edit', $course) }}" class="text-sm font-bold text-cyan hover:underline">
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