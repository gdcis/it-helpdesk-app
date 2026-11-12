@extends('layouts.app')

@section('title', 'Login - IT Helpdesk App')

@section('content')
    <div class="max-w-md mx-auto bg-white p-6 rounded-xl shadow-sm border border-slate-200 mt-6">
        <div class="mb-6 text-center">
            <h1 class="text-xl font-bold text-slate-900">Masuk ke IT Helpdesk</h1>
            <p class="text-sm text-slate-500 mt-1">Gunakan akun terdaftar untuk melaporkan atau menangani tiket.</p>
        </div>

        <form action="{{ route('login') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       placeholder="nama@perusahaan.com"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                <input type="password" name="password" required
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center gap-2 text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300">
                    <span>Ingat saya</span>
                </label>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium py-2.5 rounded-lg transition">
                Login
            </button>
        </form>

        <p class="text-center text-xs text-slate-500 mt-6">
            Belum memiliki akun?
            <a href="{{ route('register') }}" class="text-blue-600 font-semibold hover:underline">Daftar sekarang</a>
        </p>
    </div>
@endsection