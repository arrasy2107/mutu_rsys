<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class ForceChangePasswordController extends Controller
{
    /**
     * Tampilkan halaman ganti password wajib.
     */
    public function show(Request $request)
    {
        // Jika sudah tidak perlu ganti, redirect ke dashboard
        if (! $request->user()->must_change_password) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/ForceChangePassword', [
            'userName' => $request->user()->name,
        ]);
    }

    /**
     * Proses penggantian password.
     */
    public function update(Request $request)
    {
        $request->validate([
            'password' => [
                'required',
                'confirmed',
                Password::min(8),
            ],
        ], [
            'password.required'  => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min'       => 'Password minimal 8 karakter.',
        ]);

        // Pastikan password baru tidak sama dengan password default
        if (Hash::check('12345678', $request->user()->password)) {
            if ($request->password === '12345678') {
                return back()->withErrors([
                    'password' => 'Password baru tidak boleh sama dengan password default (12345678).',
                ]);
            }
        }

        $request->user()->update([
            'password'             => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Password berhasil diubah. Selamat datang, ' . $request->user()->name . '!');
    }
}
