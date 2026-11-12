@extends('layouts.app')

@section('title', 'Daftar Akun - IT Helpdesk App')

@section('content')
    <div class="max-w-md mx-auto bg-white p-6 rounded-xl shadow-sm border border-slate-200 mt-6">
        <div class="mb-6 text-center">
            <h1 class="text-xl font-bold text-slate-900">Registrasi Akun Baru</h1>
            <p class="text-sm text-slate-500 mt-1">Buat akun untuk mengirim laporan kendala IT Support.</p>
        </div>

        <form action="{{ route('register') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap</label>
                <input type="text" name="name" value="{{ old('name') }}" required autofocus
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Password (min. 6 karakter)</label>
                <input type="password" name="password" required
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Konfirmasi Password</label>
                <input type="password" name="password_confirmation" required
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium py-2.5 rounded-lg transition">
                Daftar Akun
            </button>
        </form>

        <p class="text-center text-xs text-slate-500 mt-6">
            Sudah memiliki akun?
            <a href="{{ route('login') }}" class="text-blue-600 font-semibold hover:underline">Login di sini</a>
        </p>
    </div>
@endsection