@extends('layouts.app')

@section('title', 'Daftar Tiket - IT Helpdesk')

@section('content')
    <!-- Ringkasan Statistik -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
            <p class="text-xs text-slate-500 uppercase font-semibold">Total Tiket</p>
            <p class="text-2xl font-bold text-slate-800 mt-1">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
            <p class="text-xs text-blue-600 uppercase font-semibold">Open</p>
            <p class="text-2xl font-bold text-blue-700 mt-1">{{ $stats['open'] }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
            <p class="text-xs text-amber-600 uppercase font-semibold">In Progress</p>
            <p class="text-2xl font-bold text-amber-700 mt-1">{{ $stats['in_progress'] }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
            <p class="text-xs text-emerald-600 uppercase font-semibold">Resolved / Closed</p>
            <p class="text-2xl font-bold text-emerald-700 mt-1">{{ $stats['resolved'] }}</p>
        </div>
    </div>

    <!-- Filter & Pencarian -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6">
        <form method="GET" action="{{ route('tickets.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Cari nomor tiket atau judul..."
                   class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">

            <select name="status" class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Status</option>
                @foreach (['Open', 'In Progress', 'Resolved', 'Closed'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>

            <select name="priority" class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Prioritas</option>
                @foreach (['Low', 'Medium', 'High', 'Critical'] as $priority)
                    <option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ $priority }}</option>
                @endforeach
            </select>

            <div class="flex gap-2">
                <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-white text-sm font-medium px-4 py-2 rounded-lg flex-1">
                    Filter
                </button>
                <a href="{{ route('tickets.index') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 text-sm font-medium px-3 py-2 rounded-lg">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Tabel Tiket -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-600 uppercase">
                        <th class="py-3 px-4">No. Tiket</th>
                        <th class="py-3 px-4">Judul & Kategori</th>
                        <th class="py-3 px-4">Prioritas</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Dibuat</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-sm">
                    @forelse ($tickets as $ticket)
                        @php
                            $priorityColor = match($ticket->priority) {
                                'Critical' => 'bg-red-100 text-red-700',
                                'High' => 'bg-orange-100 text-orange-700',
                                'Medium' => 'bg-blue-100 text-blue-700',
                                default => 'bg-slate-100 text-slate-700',
                            };
                            $statusColor = match($ticket->status) {
                                'Open' => 'bg-blue-100 text-blue-700',
                                'In Progress' => 'bg-amber-100 text-amber-700',
                                'Resolved' => 'bg-emerald-100 text-emerald-700',
                                default => 'bg-slate-200 text-slate-700',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 px-4 font-mono text-xs font-semibold text-blue-600">
                                <a href="{{ route('tickets.show', $ticket) }}" class="hover:underline">
                                    {{ $ticket->ticket_number }}
                                </a>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-medium text-slate-900">{{ $ticket->title }}</div>
                                <div class="text-xs text-slate-500">{{ $ticket->category }} &bull; {{ $ticket->user->name ?? 'User' }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $priorityColor }}">
                                    {{ $ticket->priority }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                                    {{ $ticket->status }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-xs text-slate-500">
                                {{ $ticket->created_at->format('d M Y H:i') }}
                            </td>
                            <td class="py-3 px-4 text-right space-x-2">
                                <a href="{{ route('tickets.show', $ticket) }}" class="text-blue-600 hover:underline text-xs font-medium">Detail</a>
                                <a href="{{ route('tickets.edit', $ticket) }}" class="text-amber-600 hover:underline text-xs font-medium">Edit</a>
                                <form action="{{ route('tickets.destroy', $ticket) }}" method="POST" class="inline" onsubmit="return confirm('Hapus tiket {{ $ticket->ticket_number }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline text-xs font-medium">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500">
                                Belum ada data tiket. Klik <strong>+ Buat Tiket Baru</strong> untuk menambahkan laporan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($tickets->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
@endsection