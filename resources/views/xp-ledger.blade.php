@extends('layouts.app', ['role' => $role])

@section('title', 'XP Ledger')

@section('content')
    <x-page-header
        title="XP Ledger"
        subtitle="Point balance and recent transactions. Complete missions and Boss Challenges to earn XP."
        icon="✦"
    >
        <x-slot:actions>
            <x-badge tone="phosphor">XP {{ $balance }}</x-badge>
        </x-slot:actions>
    </x-page-header>

    @if ($transactions->isEmpty())
        <x-status-message type="info" title="NO TRANSACTIONS">
            No point activity recorded yet. Complete missions and Boss Challenges to earn XP.
        </x-status-message>
    @endif

    <x-panel title="TRANSACTION HISTORY">
        <div class="divide-y divide-phosphor-dim/40">
            @foreach ($transactions as $transaction)
                @php
                    $credit = $transaction['direction'] === 'credit';
                    $sourceLabel = $credit
                        ? ($transaction['source'] === 'assessment' ? 'BOSS' : 'MISSION')
                        : 'SPENT';
                    $sourceTone = $credit
                        ? ($transaction['source'] === 'assessment' ? 'amber' : 'cyan')
                        : 'dim';
                @endphp
                <div class="flex items-center gap-3 py-2">
                    <span class="font-display text-[14px] leading-none {{ $credit ? 'text-phosphor' : 'text-alert' }} w-4 shrink-0 text-center">{{ $credit ? '✦' : '✕' }}</span>
                    <span class="font-body text-[15px] text-ink truncate min-w-0" title="{{ $transaction['reason'] }}">{{ $transaction['reason'] }}</span>
                    <x-badge tone="{{ $credit ? 'phosphor' : 'alert' }}">
                        {{ $transaction['amount'] > 0 ? '+' : '' }}{{ $transaction['amount'] }} XP
                    </x-badge>
                    <x-badge tone="{{ $sourceTone }}">
                        {{ $sourceLabel }}
                    </x-badge>
                    <span class="text-xs font-bold text-phosphor-dim shrink-0 ml-auto">
                        {{ $transaction['at']->format('M d, H:i') }}
                    </span>
                </div>
            @endforeach
        </div>
    </x-panel>
@endsection