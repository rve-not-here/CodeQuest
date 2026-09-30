@extends('layouts.app', ['role' => $role])

@section('title', 'New Classroom')

@section('content')
    <x-page-header
        title="New Classroom"
        subtitle="Create the classroom row first; teachers, enrolled students, and assigned courses are added afterwards as separate, explicit membership operations."
        icon="▣"
    >
        <x-slot:actions>
            <a href="{{ route('admin.classrooms') }}" class="btn-ghost">◀ ALL CLASSROOMS</a>
        </x-slot:actions>
    </x-page-header>

    @if (session('error'))
        <x-status-message type="error" title="WRITE REFUSED" class="mb-4">
            {{ session('error') }}
        </x-status-message>
    @endif

    <form method="POST" action="{{ route('admin.classrooms.store') }}" class="panel p-4 max-w-xl">
        @csrf

        <div class="mb-4">
            <label for="name" class="block text-sm font-bold text-phosphor-dim mb-1">Name</label>
            <input id="name" name="name" type="text" maxlength="128" value="{{ old('name') }}" required class="terminal-input">
            @error('name')
                <span class="text-[15px] text-red-600">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-4">
            <label for="code" class="block text-sm font-bold text-phosphor-dim mb-1">Code (optional)</label>
            <input id="code" name="code" type="text" maxlength="32" value="{{ old('code') }}" class="terminal-input">
            @error('code')
                <span class="text-[15px] text-red-600">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-6">
            <label for="status" class="block text-sm font-bold text-phosphor-dim mb-1">Status</label>
            <select id="status" name="status" class="terminal-input">
                <option value="active" @selected(old('status', \App\Models\Classroom::STATUS_ACTIVE) === 'active')>ACTIVE</option>
                <option value="inactive" @selected(old('status') === 'inactive')>INACTIVE</option>
            </select>
            <p class="mt-2 text-[15px] text-phosphor-dim">
                Status is a visibility boundary: only ACTIVE classrooms are visible to their teachers.
            </p>
            @error('status')
                <span class="text-[15px] text-red-600">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="btn-ghost">CREATE CLASSROOM →</button>
    </form>
@endsection