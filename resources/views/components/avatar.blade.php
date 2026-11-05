@props(['user'])

{{--
    Foto profil bundar; bila belum ada foto, tampil inisial nama.
    Ukuran lewat class, mis. <x-avatar :user="Auth::user()" class="h-10 w-10 text-base" />
--}}
@if ($user->avatarUrl())
    <img src="{{ $user->avatarUrl() }}" alt="Foto profil {{ $user->name }}"
         {{ $attributes->class('shrink-0 rounded-full border border-slate-200 object-cover') }}>
@else
    <span {{ $attributes->class('flex shrink-0 items-center justify-center rounded-full border border-blue-100 bg-blue-50 font-semibold text-blue-600') }}>
        {{ str($user->name)->substr(0, 1)->upper() }}
    </span>
@endif
