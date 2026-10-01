@props([
    'status' => null,
    'paymentStatus' => null,
    'statusLabel' => null,
    'paymentLabel' => null,
    'hasReceipt' => false,
])

@php
    $state = \App\Support\BornpadelMahjongTournaments::normalizeRegistrationState(
        $status,
        $paymentStatus,
        (bool) $hasReceipt
    );
    $status = $state['status'];
    $paymentStatus = $state['payment_status'];
    $statusLabel = $statusLabel
        ?: \App\Support\BornpadelMahjongTournaments::verificationStatusLabel($status);
    $paymentLabel = $paymentLabel
        ?: \App\Support\BornpadelMahjongTournaments::paymentStatusLabel($paymentStatus);
@endphp

@if ($status)
    <div {{ $attributes->merge(['class' => 'd-flex flex-wrap gap-1']) }}>
        <span class="badge status-badge-{{ $status }}" data-status-cell>{{ $statusLabel }}</span>
        <span class="badge status-badge-{{ $paymentStatus }}" data-payment-cell>{{ $paymentLabel }}</span>
    </div>
@else
    <span {{ $attributes->merge(['class' => 'text-muted small']) }}>—</span>
@endif
