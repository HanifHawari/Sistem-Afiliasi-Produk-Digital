<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Foto profil pengguna yang sedang login (disimpan di disk public: storage/app/public/avatars).
 */
class ProfileAvatarController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
        ], [], ['avatar' => 'foto profil']);

        $user = $request->user();
        $old = $user->avatar;

        $user->forceFill(['avatar' => $request->file('avatar')->store('avatars', 'public')])->save();

        if ($old) {
            Storage::disk('public')->delete($old);
        }

        return back()
            ->with('status', 'Foto profil diperbarui')
            ->with('status_detail', 'Foto baru sudah tampil di pojok kanan atas.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->forceFill(['avatar' => null])->save();
        }

        return back()->with('status', 'Foto profil dihapus');
    }
}
