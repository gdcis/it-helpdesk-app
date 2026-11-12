@extends('layouts.app')

@section('title', 'Manajemen User - IT Helpdesk App')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Manajemen Pengguna &amp; Hak Akses</h1>
            <p class="text-sm text-slate-500">Kelola role pengguna antara <strong>Admin IT</strong> dan <strong>User</strong> biasa.</p>
        </div>

        <div class="flex gap-3 text-xs">
            <div class="bg-white px-3.5 py-2 rounded-lg border border-slate-200 shadow-sm">
                Total Akun: <strong class="text-slate-900">{{ $counts['total'] }}</strong>
            </div>
            <div class="bg-white px-3.5 py-2 rounded-lg border border-slate-200 shadow-sm">
                Admin IT: <strong class="text-amber-600">{{ $counts['admin'] }}</strong>
            </div>
            <div class="bg-white px-3.5 py-2 rounded-lg border border-slate-200 shadow-sm">
                User: <strong class="text-blue-600">{{ $counts['user'] }}</strong>
            </div>
        </div>
    </div>

    <!-- Filter & Pencarian User -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6">
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Cari nama atau email pengguna..."
                   class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">

            <select name="role" class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Role</option>
                <option value="admin" @selected(request('role') === 'admin')>Admin IT</option>
                <option value="user" @selected(request('role') === 'user')>User Biasa</option>
            </select>

            <div class="flex gap-2">
                <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-white text-sm font-medium px-4 py-2 rounded-lg flex-1">
                    Cari
                </button>
                <a href="{{ route('admin.users.index') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 text-sm font-medium px-3 py-2 rounded-lg">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Tabel Daftar Pengguna -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-600 uppercase">
                        <th class="py-3 px-4">Nama &amp; Email</th>
                        <th class="py-3 px-4">Role Saat Ini</th>
                        <th class="py-3 px-4">Total Tiket</th>
                        <th class="py-3 px-4">Terdaftar</th>
                        <th class="py-3 px-4">Ubah Role</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-sm">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 px-4">
                                <div class="font-medium text-slate-900">
                                    {{ $user->name }}
                                    @if ($user->id === auth()->id())
                                        <span class="ml-1 text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded font-semibold">Akun Anda</span>
                                    @endif
                                </div>
                                <div class="text-xs text-slate-500">{{ $user->email }}</div>
                            </td>
                            <td class="py-3 px-4">
                                @if ($user->isAdmin())
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                        Admin IT
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                        User
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-xs text-slate-600">
                                {{ $user->tickets_count }} tiket
                            </td>
                            <td class="py-3 px-4 text-xs text-slate-500">
                                {{ $user->created_at->format('d M Y') }}
                            </td>
                            <td class="py-3 px-4">
                                @if ($user->id !== auth()->id())
                                    <form action="{{ route('admin.users.update-role', $user) }}" method="POST" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="role" class="border border-slate-300 rounded-lg px-2.5 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="user" @selected($user->role === 'user')>User Biasa</option>
                                            <option value="admin" @selected($user->role === 'admin')>Admin IT</option>
                                        </select>
                                        <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-medium px-3 py-1 rounded-lg transition">
                                            Simpan
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-400 italic">Terkunci</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                @if ($user->id !== auth()->id())
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Hapus akun {{ $user->name }} beserta seluruh tiketnya?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline text-xs font-medium">Hapus</button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500">
                                Tidak ada data pengguna yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection