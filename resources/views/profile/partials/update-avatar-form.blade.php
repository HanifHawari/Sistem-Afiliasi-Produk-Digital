{{-- Foto profil: pilih file -> pratinjau langsung -> Simpan. Disimpan di storage/app/public/avatars --}}
<section x-data="{ preview: null }">
    <header>
        <h2 class="card-title">Foto Profil</h2>
        <p class="mt-1 text-sm text-slate-500">Tampil di pojok kanan atas. Format JPG, PNG, atau WEBP, maksimal 2 MB.</p>
    </header>

    <form method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data"
          class="mt-6 flex flex-col gap-5 sm:flex-row sm:items-center">
        @csrf

        <div class="relative h-24 w-24 shrink-0">
            <x-avatar :user="$user" class="h-24 w-24 text-3xl" x-show="! preview" />
            <img x-show="preview" x-cloak :src="preview" alt="Pratinjau foto profil" class="h-24 w-24 rounded-full border border-slate-200 object-cover">
        </div>

        <div class="flex min-w-0 flex-col gap-3">
            <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" required
                   @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null"
                   class="w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-blue-600 hover:file:bg-blue-100">
            <x-input-error :messages="$errors->get('avatar')" />

            <div class="flex flex-wrap items-center gap-2">
                <button class="btn-primary btn-sm py-2.5">Simpan Foto</button>
                @if ($user->avatar)
                    <button type="submit" form="delete-avatar-form" class="btn-outline btn-sm py-2.5"
                            onclick="return confirm('Hapus foto profil?')">Hapus Foto</button>
                @endif
            </div>
        </div>
    </form>

    @if ($user->avatar)
        <form id="delete-avatar-form" method="POST" action="{{ route('profile.avatar.destroy') }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endif
</section>
