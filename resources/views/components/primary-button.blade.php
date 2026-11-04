<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-lg bg-primary-container px-4 py-2.5 font-label-md text-label-md text-on-primary shadow-sm transition hover:bg-[#4338CA] focus:outline-none focus:ring-2 focus:ring-[#818CF8] focus:ring-offset-2 active:scale-[0.98] disabled:opacity-50']) }}>
    {{ $slot }}
</button>
