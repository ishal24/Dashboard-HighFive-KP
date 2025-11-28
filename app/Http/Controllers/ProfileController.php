<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    /**
     * Tampilkan halaman profil
     */
    public function index()
    {
        return view('profile.index');
    }

    /**
     * Update profil pengguna (nama, email, foto)
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // Validasi input
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif', 'max:5120'], // 5MB
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'profile_image.image' => 'File harus berupa gambar.',
            'profile_image.mimes' => 'Format gambar harus JPG, PNG, atau GIF.',
            'profile_image.max' => 'Ukuran gambar maksimal 5MB.',
        ]);

        // Update nama
        $user->name = $validated['name'];

        // Update email (reset verifikasi jika email berubah)
        if ($validated['email'] !== $user->email) {
            $user->email = $validated['email'];
            $user->email_verified_at = null;
        }

        // Upload foto profil baru
        if ($request->hasFile('profile_image')) {
            try {
                // Hapus foto lama jika ada
                if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                    Storage::disk('public')->delete($user->profile_image);
                }

                // Simpan foto baru dengan nama unik
                $file = $request->file('profile_image');
                $filename = 'profile_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('profile-images', $filename, 'public');
                
                // Update database
                $user->profile_image = $path;
                
                // Log untuk debugging
                Log::info('Profile image uploaded', [
                    'user_id' => $user->id,
                    'path' => $path,
                    'full_path' => storage_path('app/public/' . $path)
                ]);
                
            } catch (\Exception $e) {
                Log::error('Failed to upload profile image: ' . $e->getMessage());
                return back()->withErrors(['profile_image' => 'Gagal mengupload foto profil.']);
            }
        }

        $user->save();

        return redirect()->route('profile.index')
            ->with('status', 'profile-updated');
    }

    /**
     * Hapus foto profil
     */
    public function removePhoto()
    {
        $user = Auth::user();

        if ($user->profile_image) {
            try {
                // Hapus file dari storage
                if (Storage::disk('public')->exists($user->profile_image)) {
                    Storage::disk('public')->delete($user->profile_image);
                }
                
                // Set kolom profile_image jadi null
                $user->profile_image = null;
                $user->save();
                
                Log::info('Profile image removed', ['user_id' => $user->id]);
                
            } catch (\Exception $e) {
                Log::error('Failed to remove profile image: ' . $e->getMessage());
            }
        }

        return redirect()->route('profile.index')
            ->with('status', 'photo-removed');
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 6 karakter.',
        ]);

        $user = Auth::user();

        // Cek password lama
        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'Password saat ini tidak sesuai.'
            ]);
        }

        // Update password
        $user->password = Hash::make($validated['password']);
        $user->save();

        return redirect()->route('profile.index')
            ->with('status', 'password-updated');
    }
}