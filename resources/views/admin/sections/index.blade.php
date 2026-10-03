@extends('layouts.app', ['role' => $role])

@section('title', $course->name.' Sections')

@section('content')
    <x-page-header
        title="Section Management"
        subtitle="{{ $course->name }} · Manage sections in learning order."
        icon="▦"
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

    <h2 class="text-sm font-bold text-phosphor mb-2">Sections in {{ $course->name }}</h2>

    <p class="result-count mb-3 text-sm text-ink">{{ $sections->count() }} {{ Str::plural('section', $sections->count()) }}</p>

    @if ($sections->isEmpty())
        <x-status-message type="info" title="NO SECTIONS">
            No sections are defined for this course yet. Awaiting directives from Command.
        </x-status-message>
    @else
        <div class="panel p-0 overflow-x-auto">
            <table class="table-stack w-full text-left">
                <thead>
                    <tr class="border-b border-phosphor-dim/60 text-sm font-bold text-phosphor-dim">
                        <th class="px-4 py-3">Order</th>
                        <th class="px-4 py-3">Section</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3">Missions</th>
                        <th class="px-4 py-3">Edit</th>
                    </tr>
                </thead>
                <tbody class="font-body text-lg">
                    @foreach ($sections as $section)
                        <tr class="border-b border-phosphor-dim/40 align-top">
                            <td class="px-4 py-3 font-display text-[10px] text-phosphor-dim" data-label="Order">
                                {{ $section->order_num }}
                            </td>

                            <td class="px-4 py-3" data-label="Section">
                                <a href="{{ route('admin.courses.sections.edit', [$course, $section]) }}" class="text-phosphor hover:underline">
                                    {{ $section->title }}
                                </a>
                            </td>

                            <td class="px-4 py-3 text-[15px] text-ink truncate max-w-[320px]" data-label="Description">
                                {{ $section->description }}
                            </td>

                            <td class="px-4 py-3 font-display text-[10px] text-phosphor-dim" data-label="Missions">
                                {{ $section->missions_count }}
                            </td>

                            <td class="px-4 py-3" data-label="Edit">
                                <a href="{{ route('admin.courses.sections.edit', [$course, $section]) }}" class="text-sm font-bold text-cyan hover:underline">
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