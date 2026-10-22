@extends('layouts.app', ['role' => $role])

@section('title', 'Classrooms')

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · Platform scope</span>
            <span class="atlas-stamp atlas-stamp--ink"><x-icon name="globe" size="12" />System-wide</span>
        </div>

        <h1 class="sr-only">Classrooms</h1>

        @if (session('status'))
            <div class="atlas-records-alerts">
                <div class="panel border-success/40 bg-success-soft px-4 py-3 text-sm" role="status">
                    <strong class="text-success">Saved</strong>
                    <span class="text-fg-muted">{{ session('status') }}</span>
                </div>
            </div>
        @endif

        <section class="atlas-record-group" aria-labelledby="classroom-directory-title">
            <div class="atlas-record-group-head">
                <h2 id="classroom-directory-title" class="atlas-record-group-title">Classroom directory</h2>
                <p class="atlas-record-group-figure">{{ $classrooms->total() }} {{ Str::plural('classroom', $classrooms->total()) }}</p>
            </div>

            <form method="GET" action="{{ route('admin.classrooms') }}" class="atlas-index-filters" aria-label="Classroom filters">
                <div class="atlas-index-filter">
                    <label for="q">Search</label>
                    <input
                        id="q"
                        name="q"
                        type="search"
                        value="{{ $filters['q'] ?? '' }}"
                        placeholder="Name or code"
                        class="terminal-input"
                     @if ($errors->has('q')) aria-invalid="true" aria-describedby="q-error" @endif>
                    @error('q')<p id="q-error" class="atlas-field-error">{{ $message }}</p>@enderror
                </div>

                <div class="atlas-index-filter">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="terminal-input" @if ($errors->has('status')) aria-invalid="true" aria-describedby="status-error" @endif>
                        <option value="">All statuses</option>
                        <option value="active" @selected(($filters['status'] ?? null) === 'active')>Active</option>
                        <option value="inactive" @selected(($filters['status'] ?? null) === 'inactive')>Inactive</option>
                    </select>
                    @error('status')<p id="status-error" class="atlas-field-error">{{ $message }}</p>@enderror
                </div>

                <div class="atlas-index-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply filters</button>
                    @if (! empty($filters['q']) || ! empty($filters['status']))
                        <a href="{{ route('admin.classrooms') }}" class="btn btn-ghost">Reset filters</a>
                    @endif
                    <a href="{{ route('admin.classrooms.create') }}" class="btn btn-secondary">New classroom →</a>
                </div>
            </form>

            @if ($errors->any())
                <div class="atlas-index-alerts">
                    <div class="panel border-warning/40 bg-warning-soft px-4 py-3 text-sm" role="alert">
                        <strong class="text-warning">Query rejected</strong>
                        <span class="text-fg-muted">{{ $errors->first() }}</span>
                    </div>
                </div>
            @endif

            @if ($classrooms->isEmpty())
                <section class="atlas-records-empty" aria-labelledby="empty-classrooms-title">
                    <h3 id="empty-classrooms-title" class="atlas-records-empty-title">No classrooms</h3>
                    <p class="atlas-records-empty-copy">No classrooms match these filters.</p>
                    @if (! empty($filters['q']) || ! empty($filters['status']))
                        <a href="{{ route('admin.classrooms') }}" class="btn btn-ghost">Reset filters →</a>
                    @endif
                </section>
            @else
                <div class="atlas-register-table-wrap">
                    <table class="table-stack atlas-register-table" role="table">
                        <caption>Classroom directory</caption>
                        <thead>
                            <tr>
                                <th scope="col">Classroom</th>
                                <th scope="col">Code</th>
                                <th scope="col">Status</th>
                                <th scope="col">Teachers</th>
                                <th scope="col">Students</th>
                                <th scope="col">Courses</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($classrooms as $classroom)
                                <tr>
                                    <th scope="row" data-label="Classroom">
                                        <a href="{{ route('admin.classrooms.edit', $classroom) }}">{{ $classroom->name }}</a>
                                    </th>

                                    <td data-label="Code"><span class="atlas-register-figure">{{ $classroom->code ?? '—' }}</span></td>

                                    <td data-label="Status">
                                        <span class="atlas-stamp {{ $classroom->status === \App\Models\Classroom::STATUS_ACTIVE ? 'atlas-stamp--grn' : 'atlas-stamp--ink' }}"><x-icon :name="$classroom->status === \App\Models\Classroom::STATUS_ACTIVE ? 'circle-check' : 'circle'" size="12" />{{ ucfirst($classroom->status) }}</span>
                                    </td>

                                    <td data-label="Teachers"><span class="atlas-register-figure">{{ $classroom->teachers_count }}</span></td>

                                    <td data-label="Students"><span class="atlas-register-figure">{{ $classroom->students_count }}</span></td>

                                    <td data-label="Courses"><span class="atlas-register-figure">{{ $classroom->courses_count }}</span></td>

                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($classrooms->hasPages())
                    <nav class="atlas-records-pager" aria-label="Classroom directory pages">
                        <p class="atlas-records-pager-figure">Showing {{ $classrooms->firstItem() }}–{{ $classrooms->lastItem() }} of {{ $classrooms->total() }} · Page {{ $classrooms->currentPage() }} of {{ $classrooms->lastPage() }}</p>
                        <div class="atlas-records-pager-actions">
                            @if ($classrooms->onFirstPage())
                                <span class="btn btn-secondary btn-sm atlas-records-pager-off" aria-disabled="true">← Previous</span>
                            @else
                                <a href="{{ $classrooms->previousPageUrl() }}" class="btn btn-secondary btn-sm">← Previous</a>
                            @endif
                            @if ($classrooms->hasMorePages())
                                <a href="{{ $classrooms->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next →</a>
                            @else
                                <span class="btn btn-secondary btn-sm atlas-records-pager-off" aria-disabled="true">Next →</span>
                            @endif
                        </div>
                    </nav>
                @endif
            @endif
        </section>
    </div>
@endsection
