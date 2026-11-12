<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TicketController extends Controller
{
    /**
     * Helper sementara untuk mendapatkan ID user aktif atau membuat user default
     * jika fitur login belum diaktifkan.
     */
    private function resolveUserId(): int
    {
        if (Auth::check()) {
            return Auth::id();
        }

        $defaultUser = User::firstOrCreate(
            ['email' => 'it.support@local.test'],
            [
                'name' => 'Staff IT Support',
                'password' => Hash::make('password123'),
            ]
        );

        return $defaultUser->id;
    }

    /**
     * Menampilkan daftar tiket dengan filter status & pencarian
     */
    public function index(Request $request): View
    {
        $query = Ticket::with('user')->latest();

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

        $stats = [
            'total' => Ticket::count(),
            'open' => Ticket::where('status', 'Open')->count(),
            'in_progress' => Ticket::where('status', 'In Progress')->count(),
            'resolved' => Ticket::whereIn('status', ['Resolved', 'Closed'])->count(),
        ];

        return view('tickets.index', compact('tickets', 'stats'));
    }

    /**
     * Form pembuatan tiket baru
     */
    public function create(): View
    {
        return view('tickets.create');
    }

    /**
     * Menyimpan tiket baru ke database Supabase
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:Hardware,Software,Network,Account,Other'],
            'priority' => ['required', 'in:Low,Medium,High,Critical'],
            'description' => ['required', 'string'],
        ]);

        $ticket = Ticket::create([
            'ticket_number' => Ticket::generateTicketNumber(),
            'user_id' => $this->resolveUserId(),
            'title' => $validated['title'],
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'description' => $validated['description'],
            'status' => 'Open',
        ]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', "Tiket {$ticket->ticket_number} berhasil dibuat.");
    }

    /**
     * Menampilkan detail tiket beserta riwayat balasan
     */
    public function show(Ticket $ticket): View
    {
        $ticket->load(['user', 'replies.user']);

        return view('tickets.show', compact('ticket'));
    }

    /**
     * Form edit tiket
     */
    public function edit(Ticket $ticket): View
    {
        return view('tickets.edit', compact('ticket'));
    }

    /**
     * Memperbarui data tiket & status penanganan
     */
    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:Hardware,Software,Network,Account,Other'],
            'priority' => ['required', 'in:Low,Medium,High,Critical'],
            'status' => ['required', 'in:Open,In Progress,Resolved,Closed'],
            'description' => ['required', 'string'],
        ]);

        $ticket->update($validated);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', "Tiket {$ticket->ticket_number} berhasil diperbarui.");
    }

    /**
     * Menghapus tiket
     */
    public function destroy(Ticket $ticket): RedirectResponse
    {
        $number = $ticket->ticket_number;
        $ticket->delete();

        return redirect()
            ->route('tickets.index')
            ->with('success', "Tiket {$number} berhasil dihapus.");
    }

    /**
     * Menambahkan balasan / catatan tindak lanjut pada tiket
     */
    public function storeReply(Request $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string'],
            'status' => ['nullable', 'in:Open,In Progress,Resolved,Closed'],
        ]);

        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->resolveUserId(),
            'message' => $validated['message'],
        ]);

        if (!empty($validated['status']) && $validated['status'] !== $ticket->status) {
            $ticket->update(['status' => $validated['status']]);
        }

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Balasan berhasil ditambahkan.');
    }
}