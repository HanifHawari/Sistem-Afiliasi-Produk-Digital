<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-lg bg-error px-4 py-2.5 font-label-md text-label-md text-on-error shadow-sm transition hover:bg-error/90 focus:outline-none focus:ring-2 focus:ring-error focus:ring-offset-2 active:scale-[0.98]']) }}>
    {{ $slot }}
</button>
