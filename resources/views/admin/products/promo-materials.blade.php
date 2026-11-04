@use('App\Enums\PromoMaterialType')

<x-layouts.admin title="Materi Promosi">
    <x-page-header eyebrow="Produk & Komisi" title="Materi Promosi" :description="$product->name">
        <x-slot name="actions">
            <a href="{{ route('admin.products.index') }}" class="flex items-center gap-space-xs rounded-lg bg-surface-container-lowest px-space-md py-2.5 text-on-surface shadow-sm hover:bg-surface-container">
                <span class="material-symbols-outlined text-[18px] text-on-surface-variant">arrow_back</span>
                <span class="font-label-md text-label-md">Kembali ke Produk</span>
            </a>
        </x-slot>
    </x-page-header>

    <div class="grid grid-cols-1 items-start gap-space-lg xl:grid-cols-12">
        {{-- Daftar materi --}}
        <div class="grid grid-cols-1 gap-space-md sm:grid-cols-2 xl:col-span-8">
            @forelse ($materials as $material)
                <div class="flex flex-col overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm">
                    <div class="flex h-40 items-center justify-center bg-surface-container-low">
                        @if ($material->type === PromoMaterialType::Image && $material->file_path)
                            <img src="{{ asset('storage/'.$material->file_path) }}" alt="{{ $material->title }}" class="h-full w-full object-cover">
                        @elseif ($material->type === PromoMaterialType::Video && $material->file_path)
                            <video src="{{ asset('storage/'.$material->file_path) }}" class="h-full w-full object-cover" controls preload="metadata"></video>
                        @else
                            <p class="line-clamp-5 px-space-md font-body-sm text-body-sm text-on-surface-variant">{{ $material->caption }}</p>
                        @endif
                    </div>
                    <div class="flex items-start justify-between gap-2 p-space-md">
                        <div class="min-w-0">
                            <p class="truncate font-label-md text-label-md text-on-surface">{{ $material->title }}</p>
                            <x-badge tone="info" :dot="false" class="mt-1">{{ $material->type->label() }}</x-badge>
                        </div>
                        <form method="POST" action="{{ route('admin.promo-materials.destroy', $material) }}" onsubmit="return confirm('Hapus materi ini?')">
                            @csrf @method('DELETE')
                            <button class="rounded-lg p-1.5 text-error hover:bg-error-container" title="Hapus">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center gap-1 rounded-xl bg-surface-container-lowest p-space-2xl text-center text-on-surface-variant shadow-sm sm:col-span-2">
                    <span class="material-symbols-outlined text-[40px] text-outline">perm_media</span>
                    <span class="font-label-md text-label-md text-on-surface">Belum ada materi promosi</span>
                    <span class="font-caption text-caption">Tambahkan banner, video, atau caption agar afiliator lebih mudah berjualan.</span>
                </div>
            @endforelse
        </div>

        {{-- Form tambah --}}
        <form method="POST" action="{{ route('admin.products.promo-materials.store', $product->id) }}" enctype="multipart/form-data"
              x-data="{ type: @js(old('type', PromoMaterialType::Image->value)) }"
              class="flex flex-col gap-3 rounded-xl bg-surface-container-lowest p-space-lg shadow-sm xl:col-span-4">
            @csrf
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[22px] text-primary">add_photo_alternate</span>
                <h3 class="font-headline-sm text-headline-sm text-on-surface">Tambah Materi</h3>
            </div>

            <div>
                <label for="title" class="font-label-sm text-label-sm text-on-surface">Judul</label>
                <input id="title" name="title" value="{{ old('title') }}" required placeholder="Contoh: Banner Instagram Story"
                       class="mt-1 w-full rounded-lg border-0 bg-surface-container-low px-3 py-2 focus:bg-surface-container focus:ring-2 focus:ring-primary-container">
                <x-input-error :messages="$errors->get('title')" class="mt-1" />
            </div>

            <div>
                <span class="font-label-sm text-label-sm text-on-surface">Jenis</span>
                <div class="mt-1 grid grid-cols-3 gap-2">
                    @foreach (PromoMaterialType::cases() as $type)
                        <label class="cursor-pointer">
                            <input type="radio" name="type" value="{{ $type->value }}" x-model="type" class="peer sr-only">
                            <span class="block rounded-lg border border-outline-variant px-2 py-2 text-center font-label-sm text-label-sm text-on-surface-variant peer-checked:border-2 peer-checked:border-primary-container peer-checked:text-primary">{{ $type->label() }}</span>
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('type')" class="mt-1" />
            </div>

            <div x-show="type !== 'text'">
                <label for="file" class="font-label-sm text-label-sm text-on-surface" x-text="type === 'video' ? 'File video (mp4/mov/webm)' : 'File gambar (jpg/png/webp)'"></label>
                <input id="file" name="file" type="file"
                       class="mt-1 w-full font-body-sm text-body-sm text-on-surface-variant file:mr-2 file:rounded-lg file:border-0 file:bg-primary-fixed file:px-3 file:py-1.5 file:font-label-sm file:text-on-primary-fixed-variant">
                <x-input-error :messages="$errors->get('file')" class="mt-1" />
            </div>

            <div>
                <label for="caption" class="font-label-sm text-label-sm text-on-surface">
                    Caption <span x-show="type !== 'text'" class="text-on-surface-variant">(opsional)</span>
                </label>
                <textarea id="caption" name="caption" rows="4" placeholder="Teks siap salin untuk afiliator"
                          class="mt-1 w-full rounded-lg border-0 bg-surface-container-low px-3 py-2 focus:bg-surface-container focus:ring-2 focus:ring-primary-container">{{ old('caption') }}</textarea>
                <x-input-error :messages="$errors->get('caption')" class="mt-1" />
            </div>

            <button class="mt-1 rounded-lg bg-primary px-4 py-2.5 font-label-md text-label-md text-on-primary shadow-sm hover:opacity-95">Simpan Materi</button>
        </form>
    </div>
</x-layouts.admin>
