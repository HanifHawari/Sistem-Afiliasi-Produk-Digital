@use('App\Enums\PromoMaterialType')

<x-layouts.admin title="Materi Promosi">
    <x-page-header title="Materi Promosi" :description="$product->name">
        <x-slot name="actions">
            <a href="{{ route('admin.products.index') }}" class="btn-outline">Kembali ke Produk</a>
        </x-slot>
    </x-page-header>

    <div class="grid grid-cols-1 items-start gap-6 2xl:grid-cols-12">
        {{-- Daftar materi --}}
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 2xl:col-span-8">
            @forelse ($materials as $material)
                <div class="card flex flex-col overflow-hidden">
                    <div class="flex h-44 items-center justify-center border-b border-slate-100 bg-slate-50">
                        @if ($material->type === PromoMaterialType::Image && $material->file_path)
                            <img src="{{ asset('storage/'.$material->file_path) }}" alt="{{ $material->title }}" class="h-full w-full object-cover">
                        @elseif ($material->type === PromoMaterialType::Video && $material->file_path)
                            <video src="{{ asset('storage/'.$material->file_path) }}" class="h-full w-full object-cover" controls preload="metadata"></video>
                        @else
                            <p class="line-clamp-5 px-6 text-sm text-slate-500">{{ $material->caption }}</p>
                        @endif
                    </div>
                    <div class="flex items-center justify-between gap-3 p-5">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-800">{{ $material->title }}</p>
                            <span class="pill-blue mt-1.5">{{ $material->type->label() }}</span>
                        </div>
                        <form method="POST" action="{{ route('admin.promo-materials.destroy', $material) }}" onsubmit="return confirm('Hapus materi ini?')">
                            @csrf @method('DELETE')
                            <button class="btn-danger btn-sm">Hapus</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="card px-7 py-14 text-center sm:col-span-2">
                    <p class="font-semibold text-slate-800">Belum ada materi promosi</p>
                    <p class="mt-1 text-sm text-slate-500">Tambahkan banner, video, atau caption agar afiliator lebih mudah berjualan.</p>
                </div>
            @endforelse
        </div>

        {{-- Form tambah --}}
        <form method="POST" action="{{ route('admin.products.promo-materials.store', $product->id) }}" enctype="multipart/form-data"
              x-data="{ type: @js(old('type', PromoMaterialType::Image->value)) }"
              class="card 2xl:col-span-4">
            @csrf
            <div class="card-header">
                <h2 class="card-title">Tambah Materi</h2>
            </div>

            <div class="card-body flex flex-col gap-4">
                <div>
                    <label for="title" class="mb-1.5 block text-sm font-semibold text-slate-700">Judul</label>
                    <input id="title" name="title" value="{{ old('title') }}" required placeholder="Contoh: Banner Instagram Story" class="input">
                    <x-input-error :messages="$errors->get('title')" class="mt-1" />
                </div>

                <div>
                    <span class="mb-1.5 block text-sm font-semibold text-slate-700">Jenis</span>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach (PromoMaterialType::cases() as $type)
                            <label class="cursor-pointer">
                                <input type="radio" name="type" value="{{ $type->value }}" x-model="type" class="peer sr-only">
                                <span class="block rounded-xl border border-slate-200 py-2.5 text-center text-sm font-semibold text-slate-500 peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-checked:text-white">{{ $type->label() }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('type')" class="mt-1" />
                </div>

                <div x-show="type !== 'text'">
                    <label for="file" class="mb-1.5 block text-sm font-semibold text-slate-700" x-text="type === 'video' ? 'File video (mp4/mov/webm)' : 'File gambar (jpg/png/webp)'"></label>
                    <input id="file" name="file" type="file"
                           class="w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-blue-600">
                    <x-input-error :messages="$errors->get('file')" class="mt-1" />
                </div>

                <div>
                    <label for="caption" class="mb-1.5 block text-sm font-semibold text-slate-700">
                        Caption <span x-show="type !== 'text'" class="font-normal text-slate-400">(opsional)</span>
                    </label>
                    <textarea id="caption" name="caption" rows="4" placeholder="Teks siap salin untuk afiliator" class="input">{{ old('caption') }}</textarea>
                    <x-input-error :messages="$errors->get('caption')" class="mt-1" />
                </div>

                <button class="btn-primary w-full">Simpan Materi</button>
            </div>
        </form>
    </div>
</x-layouts.admin>
