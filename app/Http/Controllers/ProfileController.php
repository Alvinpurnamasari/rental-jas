<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    
public function update(Request $request)
{
    $user = $request->user();

    // Hapus foto profil jika tombol Hapus Foto Profil ditekan.
if ($request->input('delete_avatar') === '1') {
    $oldAvatar = $user->avatar;

    // Kosongkan kolom avatar di database.
    $user->avatar = null;
    $user->save();

    // Hapus file foto milik akun ini dari storage.
    if ($oldAvatar && !str_starts_with($oldAvatar, 'http')) {
        Storage::disk('public')->delete($oldAvatar);
    }

    return redirect()
        ->route('profile.edit')
        ->with('status', 'Foto profil berhasil dihapus.');
}

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => [
            'required',
            'email',
            'max:255',
            'unique:users,email,' . $user->id,
        ],
        'avatar' => [
            'nullable',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:2048',
        ],
    ], [
        'avatar.image' => 'File foto harus berupa gambar.',
        'avatar.mimes' => 'Foto harus berformat JPG, JPEG, PNG, atau WEBP.',
        'avatar.max' => 'Ukuran foto maksimal 2 MB.',
    ]);

    $user->name = $validated['name'];
    $user->email = $validated['email'];
    $oldAvatar = $user->avatar;

    if ($request->hasFile('avatar')) {
        $path = $request->file('avatar')->store('avatars', 'public');

        $user->avatar = $path;
    }

    $user->save();
    // Hapus foto lama setelah foto baru berhasil disimpan.
if (
    $request->hasFile('avatar') &&
    $oldAvatar &&
    !str_starts_with($oldAvatar, 'http')
) {
    Storage::disk('public')->delete($oldAvatar);
}

    return redirect()
        ->route('profile.edit')
        ->with('status', 'Profil berhasil diperbarui.');
}


    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('profile.edit')->with('status', 'Password updated.');
    }

    public function updateTheme(Request $request)
    {
        $request->validate([
            'theme' => ['required', 'string', 'in:light,dark'],
        ]);

        $request->user()->update([
            'theme' => $request->theme,
        ]);

        return back()->with('status', 'Theme updated.');
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}