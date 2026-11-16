<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    /**
     * Menampilkan daftar seluruh user & jumlah tiket masing-masing
     */
    public function index(Request $request): View
    {
        // Urutkan pendaftar Pending di paling atas, lalu berdasarkan waktu daftar terbaru
        $query = User::withCount('tickets')
            ->orderBy('is_approved', 'asc')
            ->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('approval')) {
            if ($request->input('approval') === 'pending') {
                $query->where('is_approved', false);
            } elseif ($request->input('approval') === 'approved') {
                $query->where('is_approved', true);
            }
        }

        $users = $query->paginate(10)->withQueryString();

        $counts = [
            'total' => User::count(),
            'pending' => User::where('is_approved', false)->count(),
            'admin' => User::where('role', 'admin')->count(),
            'user' => User::where('role', 'user')->count(),
        ];

        return view('users.index', compact('users', 'counts'));
    }

    /**
     * Menyetujui / mengaktifkan akun user baru
     */
    public function approve(User $user): RedirectResponse
    {
        if ($user->isApproved()) {
            return back()->with('success', "Akun {$user->name} sudah berstatus aktif.");
        }

        $user->update(['is_approved' => true]);

        return back()->with('success', "Akun {$user->name} ({$user->email}) berhasil disetujui dan kini dapat login.");
    }

    /**
     * Mengubah role user ('admin' <-> 'user')
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->withErrors([
                'role' => 'Anda tidak dapat mengubah role akun Anda sendiri.',
            ]);
        }

        $validated = $request->validate([
            'role' => ['required', 'in:admin,user'],
        ]);

        // Mencegah sistem kehilangan Admin IT terakhir
        if ($user->role === 'admin' && $validated['role'] === 'user' && User::where('role', 'admin')->count() <= 1) {
            return back()->withErrors([
                'role' => 'Tidak dapat menurunkan role karena minimal harus ada 1 akun Admin IT aktif.',
            ]);
        }

        $user->update(['role' => $validated['role']]);

        $roleLabel = $validated['role'] === 'admin' ? 'Admin IT' : 'User';

        return back()->with('success', "Role untuk {$user->name} berhasil diubah menjadi {$roleLabel}.");
    }

    /**
     * Menghapus akun user
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->withErrors([
                'user' => 'Anda tidak dapat menghapus akun Anda sendiri yang sedang login.',
            ]);
        }

        $name = $user->name;
        $user->delete();

        return back()->with('success', "Akun {$name} berhasil dihapus.");
    }
}