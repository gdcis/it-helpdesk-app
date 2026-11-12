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
        $query = User::withCount('tickets')->latest();

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

        $users = $query->paginate(10)->withQueryString();

        $counts = [
            'total' => User::count(),
            'admin' => User::where('role', 'admin')->count(),
            'user' => User::where('role', 'user')->count(),
        ];

        return view('users.index', compact('users', 'counts'));
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