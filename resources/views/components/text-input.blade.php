@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-lg border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 shadow-sm focus:border-primary-container focus:ring-primary-container disabled:bg-surface-container-low']) }}>
