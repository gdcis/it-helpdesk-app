@extends('layouts.app')

@section('title', 'Buat Tiket Baru - IT Helpdesk')

@section('content')
    <div class="max-w-2xl mx-auto bg-white p-6 rounded-xl shadow-sm border border-slate-200">
        <div class="mb-6">
            <h1 class="text-xl font-bold text-slate-900">Buat Tiket IT Support Baru</h1>
            <p class="text-sm text-slate-500">Jelaskan kendala teknis secara spesifik agar tim IT dapat segera menindaklanjuti.</p>
        </div>

        <form action="{{ route('tickets.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Judul Kendala</label>
                <input type="text" name="title" value="{{ old('title') }}" required
                       placeholder="Contoh: Printer kasir tidak terdeteksi di jaringan"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Kategori</label>
                    <select name="category" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach (['Hardware', 'Software', 'Network', 'Account', 'Other'] as $category)
                            <option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Prioritas</label>
                    <select name="priority" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach (['Low', 'Medium', 'High', 'Critical'] as $priority)
                            <option value="{{ $priority }}" @selected(old('priority', 'Medium') === $priority)>{{ $priority }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi Detail</label>
                <textarea name="description" rows="5" required
                          placeholder="Tuliskan kronologi error, lokasi unit/ruangan, dan pesan error yang muncul..."
                          class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Lampiran Screenshot (Opsional)</label>
                <input type="file" name="attachment" accept="image/png,image/jpeg,image/webp"
                       class="w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-300 rounded-lg p-1.5">
                <p class="text-xs text-slate-400 mt-1">Format: JPG, PNG, atau WEBP. Maksimal ukuran file 2 MB.</p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <a href="{{ route('tickets.index') }}" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Batal</a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium px-5 py-2 rounded-lg">
                    Kirim Tiket
                </button>
            </div>
        </form>
    </div>
@endsection