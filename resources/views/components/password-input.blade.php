@props(['disabled' => false])

{{--
    Input password dengan tombol mata untuk menampilkan / menyembunyikan isinya.
    Atribut class dipasang di pembungkus (jarak & lebar), atribut lain (id, name, required, ...) di input.
    Contoh: <x-password-input id="password" name="password" class="mt-1.5 block w-full" required />
--}}
<div x-data="{ show: false }" {{ $attributes->only('class')->class('relative') }}>
    <input type="password" :type="show ? 'text' : 'password'" @disabled($disabled)
           {{ $attributes->except(['class', 'type'])->merge(['class' => 'input w-full pr-12 disabled:bg-slate-50']) }}>

    <button type="button" @click="show = ! show"
            :aria-pressed="show.toString()" :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'"
            :title="show ? 'Sembunyikan password' : 'Tampilkan password'"
            class="absolute inset-y-0 right-0 flex w-12 items-center justify-center rounded-r-xl text-slate-400 transition hover:text-slate-700 focus-visible:text-blue-600 focus-visible:outline-none">
        <span class="material-symbols-outlined text-[20px]" x-text="show ? 'visibility_off' : 'visibility'">visibility</span>
    </button>
</div>
