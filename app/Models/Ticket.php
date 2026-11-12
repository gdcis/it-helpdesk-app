<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'user_id',
        'title',
        'description',
        'category',
        'priority',
        'status',
    ];

    /**
     * Relasi ke pelapor (User)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke balasan / riwayat penanganan tiket
     */
    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class);
    }

    /**
     * Mendapatkan URL publik file screenshot di Supabase Storage
     */
    public function getAttachmentUrlAttribute(): ?string
    {
        if (! $this->attachment_path) {
            return null;
        }

        return Storage::disk('s3')->url($this->attachment_path);
    }

    /**
     * Generate nomor tiket otomatis: TKT-YYYYMMDD-XXXX
     */
    public static function generateTicketNumber(): string
    {
        $prefix = 'TKT-' . now()->format('Ymd') . '-';
        $lastTicket = self::where('ticket_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        $nextNumber = $lastTicket
            ? ((int) substr($lastTicket->ticket_number, -4)) + 1
            : 1;

        return $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}