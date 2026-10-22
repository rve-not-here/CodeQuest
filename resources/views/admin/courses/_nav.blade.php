@props(['course', 'active' => 'overview'])

@php
    $tabs = [
        'overview' => ['label' => 'Overview', 'href' => route('admin.courses.edit', $course)],
        'sections' => ['label' => 'Sections', 'href' => route('admin.courses.sections', $course)],
        'missions' => ['label' => 'Challenges', 'href' => route('admin.courses.missions', $course)],
        'assessment' => ['label' => 'Boss Challenge', 'href' => route('admin.courses.assessment', $course)],
    ];
@endphp

<nav class="atlas-course-nav" aria-label="Course workspace">
    @foreach ($tabs as $key => $tab)
        <a href="{{ $tab['href'] }}" @if ($active === $key) aria-current="page" @endif>{{ $tab['label'] }}</a>
    @endforeach
</nav>
