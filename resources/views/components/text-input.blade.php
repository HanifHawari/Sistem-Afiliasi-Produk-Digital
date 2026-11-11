@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'input disabled:bg-slate-50']) }}>
