<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $days = in_array((int) $request->input('days'), [7, 30, 90], true)
            ? (int) $request->input('days')
            : 30;

        $startDate = now()->subDays($days)->startOfDay();

        $baseQuery = Ticket::query()->where('created_at', '>=', $startDate);

        if (! $user->isAdmin()) {
            $baseQuery->where('user_id', $user->id);
        }

        // 1. KPI Utama
        $totalTickets = (clone $baseQuery)->count();
        $openTickets = (clone $baseQuery)->where('status', 'Open')->count();
        $inProgressTickets = (clone $baseQuery)->where('status', 'In Progress')->count();
        $resolvedTickets = (clone $baseQuery)->whereIn('status', ['Resolved', 'Closed'])->count();

        $resolutionRate = $totalTickets > 0
            ? round(($resolvedTickets / $totalTickets) * 100, 1)
            : 0;

        // 2. Rata-rata Waktu Penyelesaian (dalam Jam) untuk tiket berstatus Resolved / Closed
        $resolvedCollection = (clone $baseQuery)
            ->whereIn('status', ['Resolved', 'Closed'])
            ->get(['category', 'created_at', 'updated_at']);

        $totalResolutionHours = 0;
        foreach ($resolvedCollection as $t) {
            $totalResolutionHours += $t->created_at->diffInMinutes($t->updated_at) / 60;
        }

        $avgResolutionHours = $resolvedCollection->count() > 0
            ? round($totalResolutionHours / $resolvedCollection->count(), 1)
            : 0;

        // 3. Jumlah Tiket & Rata-rata Waktu Penyelesaian per Kategori
        $categories = ['Hardware', 'Software', 'Network', 'Account', 'Other'];
        $categoryCountsRaw = (clone $baseQuery)
            ->select('category', DB::raw('COUNT(*) as total'))
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();

        $categoryCounts = [];
        $categoryAvgHours = [];

        foreach ($categories as $cat) {
            $categoryCounts[] = $categoryCountsRaw[$cat] ?? 0;

            $catResolved = $resolvedCollection->where('category', $cat);
            if ($catResolved->count() > 0) {
                $sumHours = $catResolved->sum(fn ($item) => $item->created_at->diffInMinutes($item->updated_at) / 60);
                $categoryAvgHours[] = round($sumHours / $catResolved->count(), 1);
            } else {
                $categoryAvgHours[] = 0;
            }
        }

        // 4. Distribusi Status
        $statuses = ['Open', 'In Progress', 'Resolved', 'Closed'];
        $statusCountsRaw = (clone $baseQuery)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $statusCounts = array_map(fn ($st) => $statusCountsRaw[$st] ?? 0, $statuses);

        // 5. Tren Tiket Masuk Harian (7 Hari Terakhir)
        $trendLabels = [];
        $trendData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $trendLabels[] = $date->format('d M');

            $dailyQuery = Ticket::whereDate('created_at', $date);
            if (! $user->isAdmin()) {
                $dailyQuery->where('user_id', $user->id);
            }
            $trendData[] = $dailyQuery->count();
        }

        return view('dashboard', compact(
            'days',
            'totalTickets',
            'openTickets',
            'inProgressTickets',
            'resolvedTickets',
            'resolutionRate',
            'avgResolutionHours',
            'categories',
            'categoryCounts',
            'categoryAvgHours',
            'statuses',
            'statusCounts',
            'trendLabels',
            'trendData'
        ));
    }
}