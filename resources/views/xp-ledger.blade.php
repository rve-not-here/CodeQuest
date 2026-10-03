@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', 'XP Ledger')

@section('content')
    <div class="mx-auto w-full max-w-[1080px]">
        <header class="flex flex-wrap items-end justify-between gap-4 border-b border-line pb-6">
            <div>
                <p class="eyebrow">Learning record</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-balance md:text-3xl">XP Ledger</h1>
                <p class="mt-2 max-w-[64ch] text-sm leading-6 text-fg-muted">Your current balance and the actions that earned or spent XP.</p>
            </div>
            <a href="{{ route('timeline') }}" class="btn btn-secondary btn-sm">View Timeline →</a>
        </header>

        <section class="panel mt-6 px-5 py-5 md:px-6" aria-labelledby="balance-title">
            <h2 id="balance-title" class="eyebrow">Current balance</h2>
            <p class="mt-2 font-mono text-3xl font-semibold tabular-nums text-fg">XP {{ number_format($balance) }}</p>
            <p class="mt-2 text-xs leading-5 text-fg-muted">Challenges and Boss Challenges can earn XP. Hints, solution reveals, and incorrect submissions can spend it.</p>
        </section>

        @if ($transactions->isEmpty())
            <section class="panel mt-6 px-6 py-10 text-center" aria-labelledby="no-transactions-title">
                <h2 id="no-transactions-title" class="text-lg font-semibold">NO TRANSACTIONS</h2>
                <p class="mt-2 text-sm text-fg-muted">Your XP activity will appear here when you complete or use an XP action.</p>
            </section>
        @else
            <section class="mt-8" aria-labelledby="transactions-title">
                <div class="mb-3">
                    <p class="eyebrow">Learning record</p>
                    <h2 id="transactions-title" class="mt-1 text-lg font-semibold">Transaction history</h2>
                </div>
                <ol class="panel divide-y divide-line">
                    @foreach ($transactions as $transaction)
                        @php
                            $credit = $transaction['direction'] === 'credit';
                            $sourceLabel = $credit
                                ? ($transaction['source'] === 'assessment' ? 'BOSS' : 'CHALLENGE')
                                : 'SPENT';
                        @endphp
                        <li class="flex min-w-0 flex-wrap items-start gap-3 px-5 py-4 md:flex-nowrap md:px-6">
                            <span class="grid size-7 shrink-0 place-items-center rounded-sm border border-line font-mono text-xs {{ $credit ? 'text-accent' : 'text-warning' }}" aria-hidden="true">{{ $credit ? '+' : '−' }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm leading-6 text-fg text-pretty">{{ $transaction['reason'] }}</p>
                                <time class="mt-1 block font-mono text-xs text-fg-subtle" datetime="{{ $transaction['at']->toIso8601String() }}">{{ $transaction['at']->format('M d, H:i') }}</time>
                            </div>
                            <div class="flex shrink-0 flex-wrap items-center gap-2">
                                <span class="badge {{ $credit ? 'badge-accent' : 'badge-warning' }}">{{ $transaction['amount'] > 0 ? '+' : '' }}{{ $transaction['amount'] }} XP</span>
                                <span class="badge badge-neutral">{{ $sourceLabel }}</span>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif
    </div>
@endsection
