{{-- Halaman profil memakai layout sesuai role (admin / afiliator / toko) --}}
<x-dynamic-component :component="'layouts.'.Auth::user()->layoutArea()" title="Profil">
    <x-page-header title="Profil Saya" description="Kelola foto, nama, email, dan password akun Anda." />

    <div class="flex max-w-3xl flex-col gap-6">
        <div class="card p-6 sm:p-8">
            @include('profile.partials.update-avatar-form')
        </div>

        <div class="card p-6 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="card p-6 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        @if (Auth::user()->canDeleteAccount())
            <div class="card p-6 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        @endif
    </div>
</x-dynamic-component>
