@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-lg bg-secondary-fixed/40 px-3 py-2 font-label-sm text-label-sm text-on-secondary-fixed-variant']) }}>
        {{ $status }}
    </div>
@endif
