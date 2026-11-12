<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

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
        ]);

        $ticket = Ticket::create([
            'ticket_number' => Ticket::generateTicketNumber(),
            'user_id' => Auth::id(),
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
        ];

        // Hanya Admin IT yang boleh mengubah status tiket
        if ($user->isAdmin()) {
            $rules['status'] = ['required', 'in:Open,In Progress,Resolved,Closed'];
        }

        $validated = $request->validate($rules);
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
}