@extends('layouts.app')

@section('title', $ticket->ticket_number . ' - Detail Tiket')

@section('content')
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Header & Detail Utama Tiket -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 pb-4 mb-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-mono text-xs font-bold text-blue-600 bg-blue-50 px-2.5 py-1 rounded">{{ $ticket->ticket_number }}</span>
                        <span class="text-xs bg-slate-100 text-slate-700 px-2.5 py-1 rounded">{{ $ticket->category }}</span>
                        <span class="text-xs bg-amber-100 text-amber-800 px-2.5 py-1 rounded font-medium">{{ $ticket->priority }}</span>
                        <span class="text-xs bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded font-medium">{{ $ticket->status }}</span>
                    </div>
                    <h1 class="text-xl font-bold text-slate-900 mt-2">{{ $ticket->title }}</h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Dilaporkan oleh <strong>{{ $ticket->user->name ?? 'User' }}</strong> pada {{ $ticket->created_at->format('d M Y, H:i') }}
                    </p>
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('tickets.edit', $ticket) }}" class="bg-amber-500 hover:bg-amber-600 text-white text-xs font-medium px-3 py-2 rounded-lg">
                        Edit Tiket
                    </a>
                    <a href="{{ route('tickets.index') }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-medium px-3 py-2 rounded-lg">
                        Kembali
                    </a>
                </div>
            </div>

            <div class="prose prose-sm max-w-none text-slate-700 whitespace-pre-line">
                {{ $ticket->description }}
            </div>

            @if ($ticket->attachment_url)
                <div class="mt-6 pt-4 border-t border-slate-200">
                    <p class="text-xs font-semibold text-slate-600 uppercase mb-2">Lampiran Screenshot Kendala</p>
                    <a href="{{ $ticket->attachment_url }}" target="_blank" rel="noopener noreferrer" class="inline-block group">
                        <img src="{{ $ticket->attachment_url }}"
                             alt="Screenshot {{ $ticket->ticket_number }}"
                             class="max-h-96 rounded-lg border border-slate-300 shadow-sm group-hover:opacity-95 transition">
                        <span class="block text-xs text-blue-600 mt-1.5 group-hover:underline">Klik gambar untuk membuka ukuran penuh &nearr;</span>
                    </a>
                </div>
            @endif
        </div>

        <!-- Riwayat Tindak Lanjut / Balasan -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
            <h2 class="text-base font-bold text-slate-900 mb-4">Riwayat Penanganan & Balasan ({{ $ticket->replies->count() }})</h2>

            <div class="space-y-4 mb-6">
                @forelse ($ticket->replies as $reply)
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                        <div class="flex items-center justify-between text-xs text-slate-500 mb-2">
                            <span class="font-semibold text-slate-800">{{ $reply->user->name ?? 'Staff IT' }}</span>
                            <span>{{ $reply->created_at->format('d M Y, H:i') }}</span>
                        </div>
                        <p class="text-sm text-slate-700 whitespace-pre-line">{{ $reply->message }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 italic">Belum ada catatan tindak lanjut pada tiket ini.</p>
                @endforelse
            </div>

            <!-- Form Tambah Balasan -->
            <form action="{{ route('tickets.replies.store', $ticket) }}" method="POST" class="space-y-3 border-t border-slate-200 pt-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tambahkan Catatan Penanganan / Balasan</label>
                    <textarea name="message" rows="3" required placeholder="Tuliskan langkah perbaikan yang sudah dilakukan atau pertanyaan ke pelapor..."
                              class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    @if (auth()->user()->isAdmin() || $ticket->status === 'Open')
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-medium text-slate-600">Ubah Status Sekaligus:</label>
                        <select name="status" class="border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs">
                            @foreach (['Open', 'In Progress', 'Resolved', 'Closed'] as $status)
                                <option value="{{ $status }}" @selected($ticket->status === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    @else
                        <div></div>
                    @endif

                    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium px-4 py-2 rounded-lg">
                        Kirim Balasan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection