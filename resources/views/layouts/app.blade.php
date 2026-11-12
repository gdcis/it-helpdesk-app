<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'IT Helpdesk App')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen flex flex-col">
    <nav class="bg-slate-900 text-white shadow">
        <div class="max-w-6xl mx-auto px-4 py-4 flex flex-wrap items-center justify-between gap-4">
            <a href="{{ route('tickets.index') }}" class="flex items-center gap-2 font-bold text-lg tracking-tight">
                <span class="bg-blue-600 text-white px-2.5 py-1 rounded text-sm">IT</span>
                <span>it-helpdesk-app</span>
            </a>

            @auth
                <div class="flex items-center gap-4">
                    <a href="{{ route('tickets.index') }}" class="text-sm text-slate-300 hover:text-white">Daftar Tiket</a>
                    <a href="{{ route('tickets.create') }}" class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium px-3.5 py-1.5 rounded-lg transition">
                        + Buat Tiket
                    </a>

                    <div class="flex items-center gap-2 pl-3 border-l border-slate-700">
                        <span class="text-xs text-slate-300">{{ auth()->user()->name }}</span>
                        <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded {{ auth()->user()->isAdmin() ? 'bg-amber-500 text-slate-950' : 'bg-slate-700 text-slate-200' }}">
                            {{ auth()->user()->isAdmin() ? 'Admin IT' : 'User' }}
                        </span>
                        <form action="{{ route('logout') }}" method="POST" class="inline ml-1">
                            @csrf
                            <button type="submit" class="text-xs text-red-400 hover:text-red-300 font-medium">Logout</button>
                        </form>
                    </div>
                </div>
            @else
                <div class="flex items-center gap-3 text-sm">
                    <a href="{{ route('login') }}" class="text-slate-300 hover:text-white">Login</a>
                    <a href="{{ route('register') }}" class="bg-blue-600 hover:bg-blue-500 text-white px-3.5 py-1.5 rounded-lg font-medium">Register</a>
                </div>
            @endauth
        </div>
    </nav>

    <main class="max-w-6xl w-full mx-auto px-4 py-8 flex-1">
        @if (session('success'))
            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg text-sm flex justify-between items-center">
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="bg-white border-t border-slate-200 py-4 text-center text-xs text-slate-500">
        IT Support Ticketing System &bull; Laravel &amp; Supabase
    </footer>
</body>
</html>