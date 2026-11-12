<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $baseQuery = Ticket::query();

        // User biasa hanya melihat tiket yang dibuatnya sendiri
        if (! $user->isAdmin()) {
            $baseQuery->where('user_id', $user->id);
        }

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'open' => (clone $baseQuery)->where('status', 'Open')->count(),
            'in_progress' => (clone $baseQuery)->where('status', 'In Progress')->count(),
            'resolved' => (clone $baseQuery)->whereIn('status', ['Resolved', 'Closed'])->count(),
        ];

        $query = (clone $baseQuery)->with('user')->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'ilike', "%{$search}%")
                  ->orWhere('title', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        $tickets = $query->paginate(10)->withQueryString();

        return view('tickets.index', compact('tickets', 'stats'));
    }

    public function create(): View
    {
        return view('tickets.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:Hardware,Software,Network,Account,Other'],
            'priority' => ['required', 'in:Low,Medium,High,Critical'],
            'description' => ['required', 'string'],
            'attachment' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('screenshots', 's3');
        }

        $ticket = Ticket::create([
            'ticket_number' => Ticket::generateTicketNumber(),
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'description' => $validated['description'],
            'attachment_path' => $attachmentPath,
            'status' => 'Open',
        ]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', "Tiket {$ticket->ticket_number} berhasil dibuat.");
    }

    public function show(Ticket $ticket): View
    {
        $this->authorizeAccess($ticket);
        $ticket->load(['user', 'replies.user']);

        return view('tickets.show', compact('ticket'));
    }

    public function edit(Ticket $ticket): View
    {
        $this->authorizeAccess($ticket);

        // User biasa tidak bisa mengedit tiket jika sudah diproses IT
        if (! Auth::user()->isAdmin() && $ticket->status !== 'Open') {
            abort(403, 'Tiket yang sedang diproses atau selesai tidak dapat diubah oleh pelapor.');
        }

        return view('tickets.edit', compact('ticket'));
    }

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeAccess($ticket);
        $user = Auth::user();

        if (! $user->isAdmin() && $ticket->status !== 'Open') {
            abort(403, 'Tiket tidak dapat diubah.');
        }

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:Hardware,Software,Network,Account,Other'],
            'priority' => ['required', 'in:Low,Medium,High,Critical'],
            'description' => ['required', 'string'],
            'attachment' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        // Hanya Admin IT yang boleh mengubah status tiket
        if ($user->isAdmin()) {
            $rules['status'] = ['required', 'in:Open,In Progress,Resolved,Closed'];
        }

        $validated = $request->validate($rules);

        if ($request->hasFile('attachment')) {
            // Hapus file screenshot lama di Supabase Storage jika ada
            if ($ticket->attachment_path && Storage::disk('s3')->exists($ticket->attachment_path)) {
                Storage::disk('s3')->delete($ticket->attachment_path);
            }

            $validated['attachment_path'] = $request->file('attachment')->store('screenshots', 's3');
        } elseif ($request->boolean('remove_attachment') && $ticket->attachment_path) {
            Storage::disk('s3')->delete($ticket->attachment_path);
            $validated['attachment_path'] = null;
        }

        unset($validated['attachment']);
        $ticket->update($validated);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', "Tiket {$ticket->ticket_number} berhasil diperbarui.");
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        // Hanya Admin IT yang bisa menghapus tiket
        if (! Auth::user()->isAdmin()) {
            abort(403, 'Hanya Admin IT yang dapat menghapus tiket.');
        }

        // Bersihkan file screenshot di Supabase Storage sebelum menghapus tiket
        if ($ticket->attachment_path && Storage::disk('s3')->exists($ticket->attachment_path)) {
            Storage::disk('s3')->delete($ticket->attachment_path);
        }

        $number = $ticket->ticket_number;
        $ticket->delete();

        return redirect()
            ->route('tickets.index')
            ->with('success', "Tiket {$number} berhasil dihapus.");
    }

    public function storeReply(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeAccess($ticket);
        $user = Auth::user();

        $validated = $request->validate([
            'message' => ['required', 'string'],
            'status' => ['nullable', 'in:Open,In Progress,Resolved,Closed'],
        ]);

        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => $validated['message'],
        ]);

        // Hanya Admin IT yang bisa mengubah status saat membalas tiket
        if ($user->isAdmin() && ! empty($validated['status']) && $validated['status'] !== $ticket->status) {
            $ticket->update(['status' => $validated['status']]);
        }

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Balasan berhasil ditambahkan.');
    }

    private function authorizeAccess(Ticket $ticket): void
    {
        $user = Auth::user();
        if (! $user->isAdmin() && $ticket->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke tiket ini.');
        }
    }

    /**
     * Export laporan data tiket ke file CSV (kompatibel langsung dengan Microsoft Excel)
     */
    public function export(Request $request): StreamedResponse
    {
        $user = Auth::user();
        $query = Ticket::with(['user', 'replies'])->latest();

        // Pembatasan akses sesuai role
        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        // Terapkan filter yang sama dengan halaman index
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'ilike', "%{$search}%")
                  ->orWhere('title', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        $tickets = $query->get();
        $fileName = 'laporan-tiket-it-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($tickets) {
            $handle = fopen('php://output', 'w');

            // Tambahkan UTF-8 BOM agar Microsoft Excel otomatis mendeteksi encoding UTF-8
            fwrite($handle, "\xEF\xBB\xBF");

            // Header Kolom
            fputcsv($handle, [
                'No. Tiket',
                'Tanggal Dibuat',
                'Pelapor',
                'Email Pelapor',
                'Kategori',
                'Prioritas',
                'Status',
                'Judul Kendala',
                'Deskripsi Detail',
                'Jumlah Tindak Lanjut',
                'Terakhir Diperbarui',
                'URL Lampiran',
            ]);

            foreach ($tickets as $ticket) {
                fputcsv($handle, [
                    $ticket->ticket_number,
                    $ticket->created_at->format('Y-m-d H:i:s'),
                    $ticket->user->name ?? '-',
                    $ticket->user->email ?? '-',
                    $ticket->category,
                    $ticket->priority,
                    $ticket->status,
                    $ticket->title,
                    preg_replace("/\r|\n/", ' ', $ticket->description),
                    $ticket->replies->count(),
                    $ticket->updated_at->format('Y-m-d H:i:s'),
                    $ticket->attachment_url ?? '-',
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}