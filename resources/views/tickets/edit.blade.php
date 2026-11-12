@extends('layouts.app')

@section('title', 'Edit Tiket ' . $ticket->ticket_number)

@section('content')
    <div class="max-w-2xl mx-auto bg-white p-6 rounded-xl shadow-sm border border-slate-200">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Edit Tiket {{ $ticket->ticket_number }}</h1>
                <p class="text-sm text-slate-500">Perbarui informasi tiket{{ auth()->user()->isAdmin() ? ' atau ubah status penanganan' : '' }}.</p>
            </div>
            <span class="font-mono text-xs bg-slate-100 px-3 py-1 rounded-full text-slate-700">{{ $ticket->ticket_number }}</span>
        </div>

        <form action="{{ route('tickets.update', $ticket) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Judul Kendala</label>
                <input type="text" name="title" value="{{ old('title', $ticket->title) }}" required
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Grid otomatis 3 kolom untuk Admin IT, dan 2 kolom untuk User biasa -->
            <div class="grid grid-cols-1 {{ auth()->user()->isAdmin() ? 'md:grid-cols-3' : 'md:grid-cols-2' }} gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Kategori</label>
                    <select name="category" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        @foreach (['Hardware', 'Software', 'Network', 'Account', 'Other'] as $category)
                            <option value="{{ $category }}" @selected(old('category', $ticket->category) === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Prioritas</label>
                    <select name="priority" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        @foreach (['Low', 'Medium', 'High', 'Critical'] as $priority)
                            <option value="{{ $priority }}" @selected(old('priority', $ticket->priority) === $priority)>{{ $priority }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Dropdown Status HANYA dirender jika role user adalah Admin IT -->
                @if (auth()->user()->isAdmin())
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                        <select name="status" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                            @foreach (['Open', 'In Progress', 'Resolved', 'Closed'] as $status)
                                <option value="{{ $status }}" @selected(old('status', $ticket->status) === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi Detail</label>
                <textarea name="description" rows="5" required
                          class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description', $ticket->description) }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <a href="{{ route('tickets.show', $ticket) }}" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Batal</a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium px-5 py-2 rounded-lg">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
@endsection