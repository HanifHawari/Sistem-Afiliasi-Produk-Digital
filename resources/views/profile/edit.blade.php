{{-- Halaman profil memakai layout sesuai role (admin / afiliator / toko) --}}
<x-dynamic-component :component="'layouts.'.Auth::user()->layoutArea()" title="Profil">
    <x-page-header eyebrow="Akun" title="Profil Saya" description="Kelola nama, email, dan password akun Anda." />

    <div class="flex max-w-3xl flex-col gap-space-lg">
        <div class="rounded-xl bg-surface-container-lowest p-space-lg shadow-sm">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="rounded-xl bg-surface-container-lowest p-space-lg shadow-sm">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        @if (Auth::user()->canDeleteAccount())
            <div class="rounded-xl bg-surface-container-lowest p-space-lg shadow-sm">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        @endif
    </div>
</x-dynamic-component>
