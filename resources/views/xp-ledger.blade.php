@extends('layouts.app', ['role' => $role, 'studentPrototype' => true])

@section('title', 'XP Ledger')

@section('content')
    <div class="atlas-records">
        <div class="log-running atlas-records-running">
            <span>Records · XP ledger</span>
            <a href="{{ route('timeline') }}">Learning timeline →</a>
        </div>

        <header class="atlas-records-heading">
            <span class="atlas-lesson-entry-label">Records · Balance</span>
            <h1 class="atlas-records-title">XP ledger</h1>
            <p class="atlas-records-lede">Your current balance and every action that earned or spent XP.</p>
        </header>

        <section class="atlas-records-summary atlas-ledger-balance" aria-labelledby="balance-title">
            <h2 id="balance-title" class="atlas-lesson-entry-label">Current balance</h2>
            <p class="atlas-records-figure">XP {{ number_format($balance) }}</p>
            <p class="atlas-ledger-note">Challenges and Boss Challenges earn XP. Hints, solution reveals, and wrong submissions spend it.</p>
        </section>

        @if ($transactions->isEmpty())
            <section class="atlas-records-empty" aria-labelledby="no-transactions-title">
                <h2 id="no-transactions-title" class="atlas-records-empty-title">No transactions yet</h2>
                <p class="atlas-records-empty-copy">Complete a challenge or spend a hint and the entry is written here.</p>
            </section>
        @else
            <h2 class="atlas-records-subheading">Transaction history</h2>
            <ol class="atlas-ledger-list">
                @foreach ($transactions as $transaction)
                    @php
                        $credit = $transaction['direction'] === 'credit';
                        $sourceLabel = $credit
                            ? ($transaction['source'] === 'assessment' ? 'Boss' : 'Challenge')
                            : 'Spent';
                    @endphp
                    <li class="atlas-ledger-entry" data-direction="{{ $credit ? 'credit' : 'debit' }}">
                        <span class="atlas-ledger-mark" aria-hidden="true">{{ $credit ? '+' : '−' }}</span>
                        <div class="min-w-0">
                            <p class="atlas-ledger-reason">{{ $transaction['reason'] }}</p>
                            <p class="atlas-ledger-meta">
                                <span class="atlas-ledger-source">{{ $sourceLabel }}</span>
                                <time datetime="{{ $transaction['at']->toIso8601String() }}">{{ $transaction['at']->format('M d, H:i') }}</time>
                            </p>
                        </div>
                        <span class="atlas-ledger-amount atlas-ledger-amount--{{ $credit ? 'credit' : 'debit' }}">{{ $transaction['amount'] > 0 ? '+' : '' }}{{ $transaction['amount'] }} XP</span>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
@endsection
