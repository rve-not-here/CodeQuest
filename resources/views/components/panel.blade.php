@props(['title' => '', 'actions' => null, 'figures' => null])

@php
    $status = $status ?? null;
@endphp

<section {{ $attributes->merge(['class' => 'panel p-4 sm:p-5']) }}>
    @if ($title || $actions || ($status && ! $status->isEmpty()))
        <header class="mb-4 flex flex-wrap items-center justify-between gap-x-4 gap-y-2 border-b border-accent-line/15 pb-3">
            <div class="min-w-0 flex items-center gap-3">
                @if ($title)
                    <h2 class="panel-title">{{ $title }}</h2>
                @endif
            </div>
            @if ($status || $actions)
                <div class="flex flex-wrap items-center justify-end gap-2">
                    @if ($status && ! $status->isEmpty())
                        {!! $status !!}
                    @endif
                    @if ($actions)
                        {!! $actions !!}
                    @endif
                </div>
            @endif
        </header>
    @endif
    @if (! empty($figures))
        <dl class="panel-figures">
            @foreach ($figures as $figure)
                <div>
                    <dt>{{ $figure['label'] }}</dt>
                    <dd @if ($figure['metric'] ?? null) data-metric="{{ $figure['metric'] }}" @endif>{{ $figure['value'] }}</dd>
                </div>
            @endforeach
        </dl>
    @endif
    @if (! $slot->isEmpty())
        <div class="min-w-0">{{ $slot }}</div>
    @endif
</section>
