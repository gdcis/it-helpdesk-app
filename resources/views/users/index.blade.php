@extends('layouts.app')

@section('title', 'Manajemen User - IT Helpdesk App')

@section('content')
    @if (session('temp_password_info'))
        @php $info = session('temp_password_info'); @endphp
        <div class="mb-6 bg-amber-50 border border-amber-300 text-amber-950 p-4 rounded-xl shadow-sm flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm font-bold">Password Sementara Berhasil Dibuat</p>
                <p class="text-xs text-amber-800 mt-0.5">
                    Berikan kredensial berikut kepada <strong>{{ $info['name'] }}</strong> ({{ $info['email'] }}) dan instruksikan untuk segera mengganti password melalui menu <em>Ganti Password</em>:
                </p>
            </div>
            <div class="flex items-center gap-2 bg-white border border-amber-300 px-3 py-1.5 rounded-lg">
                <code id="tempPassText" class="font-mono text-sm font-bold text-slate-900 select-all">{{ $info['password'] }}</code>
                <button type="button"
                        onclick="navigator.clipboard.writeText(document.getElementById('tempPassText').innerText); this.innerText='Tersalin!';"
                        class="bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold px-2.5 py-1 rounded transition">
                    Salin
                </button>
            </div>
        </div>
    @endif

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Manajemen Pengguna &amp; Persetujuan Akun</h1>
            <p class="text-sm text-slate-500">Setujui pendaftaran akun baru, kelola role <strong>Admin IT</strong> / <strong>User</strong>, dan reset password.</p>
        </div>

        <div class="flex flex-wrap gap-2 text-xs">
            <div class="bg-white px-3 py-2 rounded-lg border border-slate-200 shadow-sm">
                Total: <strong class="text-slate-900">{{ $counts['total'] }}</strong>
            </div>
            <div class="bg-white px-3 py-2 rounded-lg border {{ $counts['pending'] > 0 ? 'border-red-300 bg-red-50' : 'border-slate-200' }} shadow-sm">
                Menunggu Approval: <strong class="text-red-600">{{ $counts['pending'] }}</strong>
            </div>
            <div class="bg-white px-3 py-2 rounded-lg border border-slate-200 shadow-sm">
                Admin IT: <strong class="text-amber-600">{{ $counts['admin'] }}</strong>
            </div>
            <div class="bg-white px-3 py-2 rounded-lg border border-slate-200 shadow-sm">
                User: <strong class="text-blue-600">{{ $counts['user'] }}</strong>
            </div>
        </div>
    </div>

    <!-- Filter & Pencarian User -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6">
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Cari nama atau email pengguna..."
                   class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">

            <select name="approval" class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Status Akun</option>
                <option value="pending" @selected(request('approval') === 'pending')>Menunggu Persetujuan (Pending)</option>
                <option value="approved" @selected(request('approval') === 'approved')>Sudah Disetujui (Aktif)</option>
            </select>

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
                        <th class="py-3 px-4">Status Akun</th>
                        <th class="py-3 px-4">Role</th>
                        <th class="py-3 px-4">Ubah Role</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-sm">
                    @forelse ($users as $user)
                        <tr class="{{ ! $user->isApproved() ? 'bg-amber-50/50' : 'hover:bg-slate-50' }}">
                            <td class="py-3 px-4">
                                <div class="font-medium text-slate-900">
                                    {{ $user->name }}
                                    @if ($user->id === auth()->id())
                                        <span class="ml-1 text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded font-semibold">Akun Anda</span>
                                    @endif
                                </div>
                                <div class="text-xs text-slate-500">{{ $user->email }} &bull; {{ $user->tickets_count }} tiket</div>
                            </td>
                            <td class="py-3 px-4">
                                @if ($user->isApproved())
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                        Aktif
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                        Pending Approval
                                    </span>
                                @endif
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
                            <td class="py-3 px-4 text-right space-x-2">
                                @if ($user->id !== auth()->id())
                                    @if (! $user->isApproved())
                                        <form action="{{ route('admin.users.approve', $user) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold px-3 py-1 rounded-lg transition">
                                                Approve
                                            </button>
                                        </form>
                                    @endif

                                    <form action="{{ route('admin.users.reset-password', $user) }}" method="POST" class="inline" onsubmit="return confirm('Reset password untuk {{ $user->name }}?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-amber-600 hover:underline text-xs font-medium">Reset Password</button>
                                    </form>

                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Tolak / hapus akun {{ $user->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline text-xs font-medium">
                                            {{ $user->isApproved() ? 'Hapus' : 'Tolak' }}
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('password.edit') }}" class="text-blue-600 hover:underline text-xs font-medium">Ganti Password</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-500">
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