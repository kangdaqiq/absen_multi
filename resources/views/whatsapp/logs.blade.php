@extends('layouts.app')

@section('title', 'Log WhatsApp')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        Log Pengiriman WhatsApp
    </h2>
    <div class="flex flex-wrap items-center gap-2">
        <form method="POST" action="{{ route('whatsapp-logs.clear-pending') }}" onsubmit="return confirm('Yakin ingin menghapus semua pesan antrean PENDING?')">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-warning-500 px-4 py-2 text-sm font-medium text-white hover:bg-warning-600 transition shadow-sm" style="background-color: #d97706; color: #ffffff;">
                <i class="fas fa-trash-alt"></i> Hapus Antrean Pending
            </button>
        </form>
        <form method="POST" action="{{ route('whatsapp-logs.clear-all') }}" onsubmit="return confirm('Yakin ingin menghapus SELURUH riwayat log WhatsApp?')">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-error-500 px-4 py-2 text-sm font-medium text-white hover:bg-error-600 transition shadow-sm" style="background-color: #dc2626; color: #ffffff;">
                <i class="fas fa-dumpster"></i> Hapus Semua Log
            </button>
        </form>
    </div>
</div>

@if(session('success'))
    <div class="mb-4 rounded-lg bg-success-50 border border-success-200 p-4 text-sm text-success-700 dark:bg-success-500/15 dark:text-success-400">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="mb-4 rounded-lg bg-error-50 border border-error-200 p-4 text-sm text-error-700 dark:bg-error-500/15 dark:text-error-400">
        {{ session('error') }}
    </div>
@endif

<!-- Delivery Notification Stats Today -->
<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <!-- Card 1: WA Delivery vs Absen Terkini -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <div class="flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Delivery WA vs Absen
                </span>
                <div class="mt-2 flex items-baseline gap-2">
                    <h3 class="text-2xl font-bold text-gray-800 dark:text-white">
                        {{ $deliveryStats['wa']['percent_of_absen'] }}%
                    </h3>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        ({{ $deliveryStats['wa']['sent'] }}/{{ $deliveryStats['absen_count'] }} absen)
                    </span>
                </div>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
                <i class="fab fa-whatsapp text-2xl"></i>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mt-4">
            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                <div class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                     style="width: {{ min(100, $deliveryStats['wa']['percent_of_absen']) }}%;"></div>
            </div>
            <div class="mt-2 flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400">
                <span>Sukses: {{ $deliveryStats['wa']['sent'] }}</span>
                <span>Absen: {{ $deliveryStats['absen_count'] }}</span>
            </div>
        </div>
    </div>

    <!-- Card 2: Queue Status WhatsApp -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <div class="flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Status Antrean WA Hari Ini
                </span>
                <div class="mt-2 flex items-baseline gap-2">
                    <h3 class="text-2xl font-bold text-gray-800 dark:text-white">
                        {{ $deliveryStats['wa']['total'] }}
                    </h3>
                    <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">
                        {{ $deliveryStats['wa']['success_rate'] }}% terkirim
                    </span>
                </div>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400">
                <i class="fas fa-paper-plane text-xl"></i>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-1.5 pt-1">
            <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                Sent: {{ $deliveryStats['wa']['sent'] }}
            </span>
            <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 dark:bg-amber-500/15 dark:text-amber-400">
                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                Antrean: {{ $deliveryStats['wa']['pending'] }}
            </span>
            <span class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2 py-1 text-xs font-medium text-rose-700 dark:bg-rose-500/15 dark:text-rose-400">
                <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                Gagal: {{ $deliveryStats['wa']['failed'] }}
            </span>
        </div>
    </div>

    <!-- Card 3: Absen Terkini Hari Ini -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <div class="flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Absen Terkini Hari Ini
                </span>
                <div class="mt-2 flex items-baseline gap-2">
                    <h3 class="text-2xl font-bold text-gray-800 dark:text-white">
                        {{ $deliveryStats['absen_count'] }}
                    </h3>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        orang
                    </span>
                </div>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-500/15 dark:text-purple-400">
                <i class="fas fa-user-check text-xl"></i>
            </div>
        </div>

        <div class="mt-4 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-1">
            <span>Siswa: <strong class="text-gray-700 dark:text-gray-200">{{ $deliveryStats['absen_detail']['siswa'] ?? 0 }}</strong></span>
            <span>&bull;</span>
            <span>Guru: <strong class="text-gray-700 dark:text-gray-200">{{ $deliveryStats['absen_detail']['guru'] ?? 0 }}</strong></span>
            <span>&bull;</span>
            <span>Total: <strong class="text-gray-700 dark:text-gray-200">{{ $deliveryStats['absen_count'] }}</strong></span>
        </div>
    </div>

    <!-- Card 4: Pembanding Telegram Delivery -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <div class="flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Delivery Telegram vs Absen
                </span>
                <div class="mt-2 flex items-baseline gap-2">
                    <h3 class="text-2xl font-bold text-sky-600 dark:text-sky-400">
                        {{ $deliveryStats['telegram']['percent_of_absen'] }}%
                    </h3>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        ({{ $deliveryStats['telegram']['sent'] }}/{{ $deliveryStats['absen_count'] }})
                    </span>
                </div>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-sky-50 text-sky-600 dark:bg-sky-500/15 dark:text-sky-400">
                <i class="fab fa-telegram-plane text-2xl"></i>
            </div>
        </div>

        <div class="mt-4 flex items-center justify-between pt-1">
            <span class="text-xs text-gray-500 dark:text-gray-400">
                Sent: <strong>{{ $deliveryStats['telegram']['sent'] }}</strong> | Gagal: <strong>{{ $deliveryStats['telegram']['failed'] }}</strong>
            </span>
            <a href="{{ route('telegram-logs.index') }}" class="text-xs font-semibold text-sky-600 hover:text-sky-700 dark:text-sky-400 dark:hover:text-sky-300">
                Log Telegram &rarr;
            </a>
        </div>
    </div>
</div>

<div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <!-- Header & Search -->
    <div class="flex flex-col sm:flex-row justify-between items-center px-5 py-4 border-b border-gray-200 dark:border-gray-800 gap-4">
        <h4 class="font-semibold text-gray-800 dark:text-white/90">Riwayat Pesan</h4>
        
        <form method="GET" action="{{ route('whatsapp-logs.index') }}" class="w-full sm:w-auto flex items-center">
            <div class="relative w-full sm:w-64">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nomor atau pesan..." 
                    class="w-full rounded-lg border border-gray-200 bg-transparent py-2 pl-4 pr-10 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:focus:border-brand-500 text-gray-800 dark:text-white/90">
                <button type="submit" class="absolute right-0 top-0 h-full px-3 text-gray-500 hover:text-brand-500 dark:text-gray-400">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>

    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto">
            <thead>
                <tr class="bg-gray-50 text-left dark:bg-gray-800/50 text-gray-800 dark:text-white/90 font-medium text-sm">
                    <th class="px-4 py-4 xl:pl-6 w-16">ID</th>
                    <th class="px-4 py-4 w-40">No Tujuan</th>
                    <th class="px-4 py-4">Pesan</th>
                    <th class="px-4 py-4 text-center w-32">Status</th>
                    <th class="px-4 py-4 w-48">Waktu</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors border-b border-gray-100 dark:border-gray-800 last:border-b-0">
                        <td class="px-4 py-4 xl:pl-6 align-top">
                            <p class="text-gray-500 dark:text-gray-400">#{{ $log->id }}</p>
                        </td>
                        <td class="px-4 py-4 align-top">
                            <p class="font-medium text-gray-800 dark:text-white/90 font-mono">{{ $log->phone_number }}</p>
                        </td>
                        <td class="px-4 py-4 align-top">
                            <p class="text-gray-600 dark:text-gray-400 whitespace-pre-wrap text-xs">{{ \Illuminate\Support\Str::limit($log->message, 150) }}</p>
                        </td>
                        <td class="px-4 py-4 text-center align-top">
                            @if($log->status == 'sent')
                                <span class="inline-flex rounded-full bg-success-50 px-2.5 py-1 text-xs font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">Terkirim</span>
                            @elseif($log->status == 'pending')
                                <span class="inline-flex rounded-full bg-warning-50 px-2.5 py-1 text-xs font-medium text-warning-600 dark:bg-warning-500/15 dark:text-warning-500">Pending</span>
                            @elseif($log->status == 'failed')
                                <span class="inline-flex rounded-full bg-error-50 px-2.5 py-1 text-xs font-medium text-error-600 dark:bg-error-500/15 dark:text-error-500">Gagal</span>
                                @if($log->last_error)
                                    <div class="mt-1.5 text-[11px] text-error-600 dark:text-error-400 max-w-[160px] mx-auto whitespace-normal break-words leading-tight bg-error-50 dark:bg-error-500/10 px-2 py-1.5 rounded border border-error-100 dark:border-error-500/20" title="{{ $log->last_error }}">
                                        {{ $log->last_error }}
                                    </div>
                                @endif
                            @else
                                <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">{{ $log->status }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 align-top text-xs">
                            <div class="flex flex-col gap-1 text-gray-500 dark:text-gray-400">
                                <div class="flex items-center gap-1" title="Waktu dimasukkan ke antrean">
                                    <span class="font-semibold text-gray-600 dark:text-gray-300 w-14 inline-block">Antrean:</span>
                                    <span>{{ $log->created_at->format('d/m/Y H:i:s') }}</span>
                                </div>
                                <div class="flex items-center gap-1" title="Waktu dijadwalkan untuk dikirim">
                                    <span class="font-semibold text-gray-600 dark:text-gray-300 w-14 inline-block">Jadwal:</span>
                                    <span>{{ $log->scheduled_at ? $log->scheduled_at->format('d/m/Y H:i:s') : 'Segera' }}</span>
                                </div>
                                @if($log->status === 'sent' || $log->status === 'failed')
                                    <div class="flex items-center gap-1" title="Waktu selesai diproses">
                                        <span class="font-semibold text-gray-600 dark:text-gray-300 w-14 inline-block">{{ $log->status === 'sent' ? 'Terkirim:' : 'Proses:' }}</span>
                                        <span class="{{ $log->status === 'sent' ? 'text-success-600 dark:text-success-400' : 'text-error-600 dark:text-error-400' }} font-medium">
                                            {{ $log->updated_at->format('d/m/Y H:i:s') }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            Belum ada log pesan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800">
        {{ $logs->links('vendor.pagination.tailwind') }}
    </div>
</div>
@endsection
