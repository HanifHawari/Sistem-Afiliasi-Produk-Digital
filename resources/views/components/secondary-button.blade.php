<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 font-label-md text-label-md text-slate-900 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[#818CF8] focus:ring-offset-2 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
