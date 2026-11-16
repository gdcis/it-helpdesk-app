<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Menampilkan halaman form login
     */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Memproses login & mengecek status persetujuan (is_approved)
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            // Blokir masuk jika akun belum disetujui Admin IT
            if (! $user->isApproved()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Akun Anda masih menunggu persetujuan (aktivasi) dari Admin IT.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Selamat datang kembali, ' . $user->name . '!');
        }

        return back()->withErrors([
            'email' => 'Email atau password tidak sesuai.',
        ])->onlyInput('email');
    }

    /**
     * Menampilkan halaman form pendaftaran akun baru
     */
    public function showRegister(): View
    {
        return view('auth.register');
    }

    /**
     * Memproses registrasi akun baru (Akun pertama otomatis Admin IT & aktif)
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $isFirstUser = User::count() === 0;
        $role = $isFirstUser ? 'admin' : 'user';
        $isApproved = $isFirstUser; // Hanya Admin IT pertama yang langsung aktif

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $role,
            'is_approved' => $isApproved,
        ]);

        if ($isFirstUser) {
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->route('dashboard')
                ->with('success', 'Akun Admin IT pertama berhasil dibuat dan langsung aktif.');
        }

        return redirect()->route('login')
            ->with('success', 'Registrasi berhasil! Silakan tunggu Admin IT menyetujui akun Anda sebelum login.');
    }

    /**
     * Menampilkan halaman ganti password mandiri bagi user yang sedang login
     */
    public function showChangePassword(): View
    {
        return view('auth.change-password');
    }

    /**
     * Memproses perubahan password mandiri
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:6', 'confirmed', 'different:current_password'],
        ], [
            'current_password.current_password' => 'Password saat ini yang Anda masukkan salah.',
            'password.different' => 'Password baru tidak boleh sama dengan password lama.',
        ]);

        $user = $request->user();
        $user->update([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ]);

        return back()->with('success', 'Password akun Anda berhasil diperbarui.');
    }

    /**
     * Memproses logout pengguna
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Anda telah keluar dari sistem.');
    }
}