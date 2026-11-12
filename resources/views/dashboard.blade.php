@extends('layouts.app')

@section('title', 'Dashboard Statistik - IT Helpdesk App')

@section('content')
    <!-- Header & Filter Periode -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">
                Dashboard Analitik {{ auth()->user()->isAdmin() ? 'IT Support' : 'Tiket Saya' }}
            </h1>
            <p class="text-sm text-slate-500">
                Pemantauan distribusi kendala per kategori, status penanganan, dan rata-rata waktu penyelesaian.
            </p>
        </div>

        <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
            <label for="days" class="text-xs font-medium text-slate-600">Periode:</label>
            <select name="days" id="days" onchange="this.form.submit()"
                    class="bg-white border border-slate-300 rounded-lg px-3 py-1.5 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="7" @selected($days === 7)>7 Hari Terakhir</option>
                <option value="30" @selected($days === 30)>30 Hari Terakhir</option>
                <option value="90" @selected($days === 90)>90 Hari Terakhir</option>
            </select>
        </form>
    </div>

    <!-- 4 Kartu KPI Utama -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
            <p class="text-xs font-semibold uppercase text-slate-500">Total Tiket ({{ $days }} Hari)</p>
            <p class="text-3xl font-bold text-slate-900 mt-1">{{ $totalTickets }}</p>
            <p class="text-xs text-slate-500 mt-2">
                Open: <strong class="text-blue-600">{{ $openTickets }}</strong> •
                Proses: <strong class="text-amber-600">{{ $inProgressTickets }}</strong>
            </p>
        </div>

        <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
            <p class="text-xs font-semibold uppercase text-emerald-600">Tiket Terselesaikan</p>
            <p class="text-3xl font-bold text-emerald-700 mt-1">{{ $resolvedTickets }}</p>
            <p class="text-xs text-slate-500 mt-2">Status Resolved & Closed</p>
        </div>

        <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
            <p class="text-xs font-semibold uppercase text-blue-600">Tingkat Penyelesaian</p>
            <p class="text-3xl font-bold text-blue-700 mt-1">{{ $resolutionRate }}%</p>
            <div class="w-full bg-slate-100 rounded-full h-1.5 mt-3">
                <div class="bg-blue-600 h-1.5 rounded-full" style="width: {{ min($resolutionRate, 100) }}%"></div>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
            <p class="text-xs font-semibold uppercase text-purple-600">Rata-Rata Waktu Selesai</p>
            <p class="text-3xl font-bold text-purple-700 mt-1">
                {{ $avgResolutionHours }} <span class="text-base font-medium text-slate-500">Jam</span>
            </p>
            <p class="text-xs text-slate-500 mt-2">Dihitung dari tiket masuk hingga selesai</p>
        </div>
    </div>

    <!-- Baris Grafik 1: Jumlah Tiket per Kategori & Proporsi Status -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2 bg-white p-5 rounded-xl shadow-sm border border-slate-200">
            <h2 class="text-sm font-bold text-slate-800 mb-1">Jumlah Tiket per Kategori</h2>
            <p class="text-xs text-slate-500 mb-4">Distribusi laporan kendala berdasarkan jenis layanan IT</p>
            <div class="relative h-64">
                <canvas id="categoryBarChart"></canvas>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
            <h2 class="text-sm font-bold text-slate-800 mb-1">Proporsi Status Tiket</h2>
            <p class="text-xs text-slate-500 mb-4">Komposisi tiket aktif vs selesai</p>
            <div class="relative h-64 flex items-center justify-center">
                <canvas id="statusDoughnutChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Baris Grafik 2: Rata-rata Waktu Penyelesaian per Kategori & Tren 7 Hari -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
            <h2 class="text-sm font-bold text-slate-800 mb-1">Rata-Rata Waktu Penyelesaian per Kategori (Jam)</h2>
            <p class="text-xs text-slate-500 mb-4">Durasi rata-rata penanganan tiket selesai pada tiap kategori</p>
            <div class="relative h-64">
                <canvas id="resolutionTimeChart"></canvas>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
            <h2 class="text-sm font-bold text-slate-800 mb-1">Tren Tiket Masuk (7 Hari Terakhir)</h2>
            <p class="text-xs text-slate-500 mb-4">Volume laporan harian yang diterima sistem</p>
            <div class="relative h-64">
                <canvas id="dailyTrendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Script Chart.js CDN & Inisialisasi Grafik -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const categories = @json($categories);
            const categoryCounts = @json($categoryCounts);
            const categoryAvgHours = @json($categoryAvgHours);
            const statuses = @json($statuses);
            const statusCounts = @json($statusCounts);
            const trendLabels = @json($trendLabels);
            const trendData = @json($trendData);

            // 1. Bar Chart: Jumlah Tiket per Kategori
            new Chart(document.getElementById('categoryBarChart'), {
                type: 'bar',
                data: {
                    labels: categories,
                    datasets: [{
                        label: 'Jumlah Tiket',
                        data: categoryCounts,
                        backgroundColor: ['#3b82f6', '#6366f1', '#0ea5e9', '#f59e0b', '#64748b'],
                        borderRadius: 6,
                        maxBarThickness: 48
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } }
                    }
                }
            });

            // 2. Doughnut Chart: Proporsi Status
            new Chart(document.getElementById('statusDoughnutChart'), {
                type: 'doughnut',
                data: {
                    labels: statuses,
                    datasets: [{
                        data: statusCounts,
                        backgroundColor: ['#3b82f6', '#f59e0b', '#10b981', '#94a3b8'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
                    }
                }
            });

            // 3. Horizontal Bar Chart: Rata-rata Waktu Penyelesaian (Jam) per Kategori
            new Chart(document.getElementById('resolutionTimeChart'), {
                type: 'bar',
                data: {
                    labels: categories,
                    datasets: [{
                        label: 'Rata-rata Selesai (Jam)',
                        data: categoryAvgHours,
                        backgroundColor: '#8b5cf6',
                        borderRadius: 6,
                        maxBarThickness: 28
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: {
                            beginAtZero: true,
                            title: { display: true, text: 'Jam', font: { size: 11 } }
                        }
                    }
                }
            });

            // 4. Line Chart: Tren Harian 7 Hari Terakhir
            new Chart(document.getElementById('dailyTrendChart'), {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [{
                        label: 'Tiket Masuk',
                        data: trendData,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.12)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 4,
                        pointBackgroundColor: '#2563eb'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } }
                    }
                }
            });
        });
    </script>
@endsection