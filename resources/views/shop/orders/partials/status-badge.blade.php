@props(['status'])

@php
    $styles = match ($status) {
        \App\Enums\OrderStatus::Paid => [
            'badge' => 'bg-[#ECFDF5] text-[#059669] border border-[#A7F3D0]',
            'dot' => 'bg-[#059669]',
            'pulse' => false,
        ],
        \App\Enums\OrderStatus::WaitingVerification => [
            'badge' => 'bg-[#FFFBEB] text-[#D97706] border border-[#FDE68A]',
            'dot' => 'bg-[#D97706]',
            'pulse' => true,
        ],
        \App\Enums\OrderStatus::Rejected => [
            'badge' => 'bg-[#FEF2F2] text-[#DC2626] border border-[#FECACA]',
            'dot' => 'bg-[#DC2626]',
            'pulse' => false,
        ],
        default => [
            'badge' => 'bg-[#F1F5F9] text-[#475569] border border-[#E2E8F0]',
            'dot' => 'bg-[#64748B]',
            'pulse' => false,
        ],
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 font-label-sm text-label-sm '.$styles['badge']]) }}>
    <span @class(['h-1.5 w-1.5 shrink-0 rounded-full', $styles['dot'], 'animate-pulse' => $styles['pulse']])></span>
    <span>{{ $status->label() }}</span>
</span>

