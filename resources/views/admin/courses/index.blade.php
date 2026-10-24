@extends('layouts.app', ['role' => $role])

@section('title', 'Courses')

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Platform scope</span>
            <span class="atlas-stamp atlas-stamp--ink">Ordered by order</span>
        </div>

        <h1 class="sr-only">Courses</h1>

        @if (session('status'))
            <div class="atlas-records-alerts">
                <div class="panel border-success/40 bg-success-soft px-4 py-3 text-sm" role="status">
                    <strong class="text-success">Saved</strong>
                    <span class="text-fg-muted">{{ session('status') }}</span>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="atlas-records-alerts">
                <div class="panel border-danger/40 bg-danger-soft px-4 py-3 text-sm" role="alert">
                    <strong class="text-danger">Change rejected</strong>
                    <span class="text-fg-muted">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <section class="atlas-record-group" aria-labelledby="catalog-title">
            <div class="atlas-record-group-head">
                <h2 id="catalog-title" class="atlas-record-group-title">Course catalog</h2>
                <p class="atlas-record-group-figure">{{ $courses->count() }} {{ Str::plural('course', $courses->count()) }}</p>
            </div>

            @if ($courses->isEmpty())
                <section class="atlas-records-empty" aria-labelledby="empty-courses-title">
                    <h3 id="empty-courses-title" class="atlas-records-empty-title">No courses</h3>
                    <p class="atlas-records-empty-copy">No courses are available.</p>
                </section>
            @else
                <div class="atlas-register-table-wrap">
                    <table class="table-stack atlas-register-table" role="table">
                        <caption>Course catalog</caption>
                        <thead>
                            <tr>
                                <th scope="col">Order</th>
                                <th scope="col">Course</th>
                                <th scope="col">Type</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="md:text-right">Sections</th>
                                <th scope="col" class="md:text-right">Challenges</th>
                                <th scope="col" class="md:text-right">Boss Challenge</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($courses as $course)
                                @php
                                    $statusTone = match ($course->status) {
                                        'active' => 'atlas-stamp--grn',
                                        'locked' => 'atlas-stamp--amb',
                                        default => 'atlas-stamp--ink',
                                    };
                                @endphp

                                <tr>
                                    <td data-label="Order"><span class="atlas-register-figure">{{ $course->order_num }}</span></td>

                                    <th scope="row" data-label="Course">
                                        <a href="{{ route('admin.courses.edit', $course) }}">{{ $course->name }}</a>
                                        <span class="atlas-register-note">{{ $course->slug }}</span>
                                    </th>

                                    <td data-label="Type"><span class="atlas-register-figure">{{ $course->type }}</span></td>

                                    <td data-label="Status">
                                        <span class="atlas-stamp {{ $statusTone }}"><x-icon :name="\App\Support\StampIcon::for(ucfirst($course->status)) ?? 'circle'" size="12" />{{ ucfirst($course->status) }}</span>
                                    </td>

                                    <td data-label="Sections" class="md:text-right tabular-nums">
                                        <a href="{{ route('admin.courses.sections', $course) }}" class="atlas-register-figure">{{ $course->sections_count }} →</a>
                                    </td>

                                    <td data-label="Challenges" class="md:text-right tabular-nums">
                                        <a href="{{ route('admin.courses.missions', $course) }}" class="atlas-register-figure">{{ $course->missions_count }} →</a>
                                    </td>

                                    <td data-label="Boss Challenge" class="md:text-right tabular-nums">
                                        @if ($course->assessment_count > 0)
                                            <a href="{{ route('admin.courses.assessment', $course) }}" class="atlas-register-figure">1 →</a>
                                        @else
                                            <span class="atlas-register-note">None</span>
                                        @endif
                                    </td>

                                    <td data-label="Actions">
                                        <div class="atlas-row-actions">
                                            <form method="POST" action="{{ route('admin.courses.destroy', $course) }}" onsubmit="return confirm('Delete this course permanently? Its sections, challenges and Boss Challenge are removed with it, and classroom assignments are detached. This cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="atlas-row-action atlas-row-action--danger">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="atlas-record-group-note">Deleting a course removes its sections, challenges and Boss Challenge, and detaches its classroom assignments. The action cannot be undone.</p>
            @endif
        </section>
    </div>
@endsection
