@extends('layouts.app')

@section('title', 'Paket Langganan')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        <i class="fas fa-box-open text-brand-500 mr-2"></i> Paket Langganan
    </h2>
    @if($school->expired_at)
        @php $expiredAt = $school->expired_at; @endphp
        @if($expiredAt->isPast())
            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-3 py-1 text-sm font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">
                <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse inline-block"></span>
                Langganan Kedaluwarsa {{ $expiredAt->diffForHumans() }}
            </span>
        @elseif($expiredAt->diffInDays(now()) <= 7)
            <span class="inline-flex items-center gap-1.5 rounded-full bg-yellow-100 px-3 py-1 text-sm font-medium text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                <span class="w-2 h-2 rounded-full bg-yellow-500 animate-pulse inline-block"></span>
                Kedaluwarsa {{ $expiredAt->diffForHumans() }}
            </span>
        @else
            <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>
                Aktif sampai {{ $expiredAt->format('d M Y') }}
            </span>
        @endif
    @else
        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">
            Tidak ada masa aktif
        </span>
    @endif
</div>

{{-- ── Status Aktif & Penggunaan ────────────────────────────────────────── --}}
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">

    {{-- Paket Aktif --}}
    <div class="rounded-2xl border border-stroke bg-white p-5 shadow-sm dark:border-strokedark dark:bg-boxdark col-span-1 sm:col-span-2">
        <div class="flex items-start gap-4">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-brand-500/10">
                <i class="fas fa-box text-2xl text-brand-500"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-0.5">Paket Aktif</p>
                <h3 class="text-xl font-bold text-black dark:text-white truncate">
                    {{ $activeSubscription?->package?->name ?? 'Tanpa Paket / Kustom' }}
                </h3>
                @if($activeSubscription)
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                        <span class="rounded-full bg-brand-500/10 text-brand-500 px-2 py-0.5 font-medium">
                            {{ $activeSubscription->billing_cycle === 'yearly' ? 'Tahunan' : 'Bulanan' }}
                        </span>
                        <span class="text-gray-500 dark:text-gray-400">
                            Sejak {{ $activeSubscription->started_at?->format('d M Y') ?? '-' }}
                        </span>
                        @if($activeSubscription->expired_at)
                            <span class="text-gray-500 dark:text-gray-400">
                                — Hingga {{ $activeSubscription->expired_at->format('d M Y') }}
                            </span>
                        @endif
                    </div>
                @else
                    <p class="mt-1 text-xs text-gray-400">Belum ada langganan aktif. Hubungi Admin untuk aktivasi paket.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Kuota Siswa --}}
    @php
        $studentPct = $usage['students']['limit'] > 0
            ? min(100, round($usage['students']['current'] / $usage['students']['limit'] * 100))
            : null;
    @endphp
    <div class="rounded-2xl border border-stroke bg-white p-5 shadow-sm dark:border-strokedark dark:bg-boxdark">
        <div class="flex items-center justify-between mb-3">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Kuota Siswa</p>
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                <i class="fas fa-user-graduate text-blue-500 text-sm"></i>
            </span>
        </div>
        <div class="text-2xl font-bold text-black dark:text-white mb-1">
            {{ number_format($usage['students']['current']) }}
            <span class="text-sm font-normal text-gray-400">/ {{ $usage['students']['limit'] > 0 ? number_format($usage['students']['limit']) : '∞' }}</span>
        </div>
        @if($studentPct !== null)
            <div class="mt-2">
                <div class="h-1.5 w-full rounded-full bg-gray-100 dark:bg-gray-700">
                    <div class="h-1.5 rounded-full {{ $studentPct >= 90 ? 'bg-red-500' : ($studentPct >= 70 ? 'bg-yellow-500' : 'bg-blue-500') }} transition-all"
                        style="width: {{ $studentPct }}%"></div>
                </div>
                <p class="mt-1 text-xs text-gray-400">{{ $studentPct }}% terpakai</p>
            </div>
        @else
            <p class="mt-1 text-xs text-gray-400">Unlimited</p>
        @endif
    </div>

    {{-- Kuota Guru --}}
    @php
        $teacherPct = $usage['teachers']['limit'] > 0
            ? min(100, round($usage['teachers']['current'] / $usage['teachers']['limit'] * 100))
            : null;
    @endphp
    <div class="rounded-2xl border border-stroke bg-white p-5 shadow-sm dark:border-strokedark dark:bg-boxdark">
        <div class="flex items-center justify-between mb-3">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Kuota Guru</p>
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900/30">
                <i class="fas fa-chalkboard-teacher text-purple-500 text-sm"></i>
            </span>
        </div>
        <div class="text-2xl font-bold text-black dark:text-white mb-1">
            {{ number_format($usage['teachers']['current']) }}
            <span class="text-sm font-normal text-gray-400">/ {{ $usage['teachers']['limit'] > 0 ? number_format($usage['teachers']['limit']) : '∞' }}</span>
        </div>
        @if($teacherPct !== null)
            <div class="mt-2">
                <div class="h-1.5 w-full rounded-full bg-gray-100 dark:bg-gray-700">
                    <div class="h-1.5 rounded-full {{ $teacherPct >= 90 ? 'bg-red-500' : ($teacherPct >= 70 ? 'bg-yellow-500' : 'bg-purple-500') }} transition-all"
                        style="width: {{ $teacherPct }}%"></div>
                </div>
                <p class="mt-1 text-xs text-gray-400">{{ $teacherPct }}% terpakai</p>
            </div>
        @else
            <p class="mt-1 text-xs text-gray-400">Unlimited</p>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    {{-- Kolom Kiri: Fitur Aktif & Info --}}
    <div class="flex flex-col gap-6 lg:col-span-1">

        {{-- Fitur Aktif --}}
        <div class="rounded-2xl border border-stroke bg-white p-6 shadow-sm dark:border-strokedark dark:bg-boxdark">
            <h3 class="mb-4 font-semibold text-black dark:text-white">
                <i class="fas fa-toggle-on text-brand-500 mr-2"></i> Fitur Aktif
            </h3>
            <ul class="space-y-3">
                <li class="flex items-center gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $school->wa_enabled ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30' }}">
                        <i class="fas fa-{{ $school->wa_enabled ? 'check' : 'times' }} text-xs {{ $school->wa_enabled ? 'text-green-600' : 'text-red-500' }}"></i>
                    </span>
                    <div>
                        <p class="text-sm font-medium text-black dark:text-white">Notifikasi WhatsApp</p>
                        <p class="text-xs text-gray-400">{{ $school->wa_enabled ? 'Aktif' : 'Tidak Aktif' }}</p>
                    </div>
                </li>
                <li class="flex items-center gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $school->bot_enabled ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30' }}">
                        <i class="fas fa-{{ $school->bot_enabled ? 'check' : 'times' }} text-xs {{ $school->bot_enabled ? 'text-green-600' : 'text-red-500' }}"></i>
                    </span>
                    <div>
                        <p class="text-sm font-medium text-black dark:text-white">Bot WA Interaktif</p>
                        <p class="text-xs text-gray-400">
                            {{ $school->bot_enabled ? 'Aktif' : 'Tidak Aktif' }}
                            @if($school->bot_enabled)
                                — {{ $usage['bot_users']['current'] }}/{{ $usage['bot_users']['limit'] > 0 ? $usage['bot_users']['limit'] : '∞' }} guru
                            @endif
                        </p>
                    </div>
                </li>
                <li class="flex items-center gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                        <i class="fas fa-history text-xs text-blue-500"></i>
                    </span>
                    <div>
                        <p class="text-sm font-medium text-black dark:text-white">Retensi Histori Absen</p>
                        <p class="text-xs text-gray-400">
                            @if($school->history_quota_months)
                                {{ $school->history_quota_months }} bulan terakhir
                            @else
                                Tidak Terbatas (Simpan Selamanya)
                            @endif
                        </p>
                    </div>
                </li>
            </ul>
        </div>

        {{-- Bantuan --}}
        <div class="rounded-2xl border border-brand-500/30 bg-brand-500/5 p-5 dark:border-brand-500/20 dark:bg-brand-500/10">
            <div class="flex gap-3">
                <i class="fas fa-info-circle text-brand-500 mt-0.5 shrink-0"></i>
                <div>
                    <p class="text-sm font-semibold text-brand-600 dark:text-brand-400 mb-1">Ingin upgrade atau perpanjang?</p>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Hubungi Admin/Operator kami untuk melakukan upgrade paket atau perpanjangan masa aktif langganan Anda.</p>
                    @if($school->operator_phone)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $school->operator_phone) }}"
                           target="_blank"
                           class="mt-3 inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-600 transition">
                            <i class="fab fa-whatsapp"></i> Hubungi Operator
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Kolom Kanan: Riwayat Langganan --}}
    <div class="lg:col-span-2">
        <div class="rounded-2xl border border-stroke bg-white shadow-sm dark:border-strokedark dark:bg-boxdark">
            <div class="border-b border-stroke px-6 py-4 dark:border-strokedark">
                <h3 class="font-semibold text-black dark:text-white">
                    <i class="fas fa-history text-gray-400 mr-2"></i> Riwayat Langganan
                </h3>
            </div>
            <div class="p-0">
                @if($history->isEmpty())
                    <div class="flex flex-col items-center justify-center py-16 text-center px-6">
                        <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                            <i class="fas fa-receipt text-2xl text-gray-400"></i>
                        </div>
                        <p class="font-medium text-gray-600 dark:text-gray-400">Belum ada riwayat langganan</p>
                        <p class="mt-1 text-sm text-gray-400">Riwayat aktivasi dan perpanjangan paket akan muncul di sini.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-stroke dark:border-strokedark bg-gray-50 dark:bg-gray-800/50 text-left">
                                    <th class="px-6 py-3 font-semibold text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Paket</th>
                                    <th class="px-6 py-3 font-semibold text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Siklus</th>
                                    <th class="px-6 py-3 font-semibold text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Mulai</th>
                                    <th class="px-6 py-3 font-semibold text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Hingga</th>
                                    <th class="px-6 py-3 font-semibold text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stroke dark:divide-strokedark">
                                @foreach($history as $sub)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-black dark:text-white">
                                                {{ $sub->package?->name ?? 'Paket Kustom' }}
                                            </div>
                                            @if($sub->amount > 0)
                                                <div class="text-xs text-gray-400">
                                                    Rp {{ number_format($sub->amount, 0, ',', '.') }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                                            {{ $sub->billing_cycle === 'yearly' ? 'Tahunan' : 'Bulanan' }}
                                        </td>
                                        <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                                            {{ $sub->started_at?->format('d M Y') ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                                            {{ $sub->expired_at?->format('d M Y') ?? '∞' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($sub->status === 'paid')
                                                <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                                    Aktif
                                                </span>
                                            @elseif($sub->status === 'pending')
                                                <span class="inline-flex rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                                                    Pending
                                                </span>
                                            @else
                                                <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                                    {{ ucfirst($sub->status) }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($history->hasPages())
                        <div class="px-6 py-4 border-t border-stroke dark:border-strokedark">
                            {{ $history->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── Pilihan Paket Tersedia ─────────────────────────────────────────────── --}}
@if($packages->isNotEmpty())
<div class="mt-8">
    <h3 class="mb-4 text-lg font-semibold text-black dark:text-white">
        <i class="fas fa-tags text-brand-500 mr-2"></i> Paket yang Tersedia
    </h3>
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($packages as $pkg)
            @php $isActive = $activeSubscription?->package_id === $pkg->id; @endphp
            <div class="relative rounded-2xl border {{ $isActive ? 'border-brand-500 ring-2 ring-brand-500/20' : 'border-stroke dark:border-strokedark' }} bg-white p-6 shadow-sm dark:bg-boxdark transition hover:shadow-md">
                @if($isActive)
                    <span class="absolute top-4 right-4 inline-flex rounded-full bg-brand-500 px-2.5 py-0.5 text-xs font-semibold text-white">
                        Paket Anda
                    </span>
                @endif
                <h4 class="text-lg font-bold text-black dark:text-white mb-1">{{ $pkg->name }}</h4>
                <div class="mb-4">
                    <span class="text-2xl font-extrabold text-brand-500">
                        Rp {{ $pkg->price_monthly > 0 ? number_format($pkg->price_monthly, 0, ',', '.') : 'Gratis' }}
                    </span>
                    @if($pkg->price_monthly > 0)
                        <span class="text-sm text-gray-400">/bulan</span>
                    @endif
                    @if($pkg->price_yearly > 0)
                        <p class="text-xs text-gray-400 mt-0.5">
                            atau Rp {{ number_format($pkg->price_yearly, 0, ',', '.') }}/tahun
                        </p>
                    @endif
                </div>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li class="flex items-center gap-2">
                        <i class="fas fa-check text-green-500 w-4"></i>
                        Siswa: <strong class="text-black dark:text-white">{{ $pkg->student_limit > 0 ? number_format($pkg->student_limit) : 'Unlimited' }}</strong>
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fas fa-check text-green-500 w-4"></i>
                        Guru: <strong class="text-black dark:text-white">{{ $pkg->teacher_limit > 0 ? number_format($pkg->teacher_limit) : 'Unlimited' }}</strong>
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fas fa-{{ $pkg->wa_enabled ? 'check text-green-500' : 'times text-red-400' }} w-4"></i>
                        Notifikasi WhatsApp
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fas fa-{{ $pkg->bot_enabled ? 'check text-green-500' : 'times text-red-400' }} w-4"></i>
                        Bot WA Interaktif
                        @if($pkg->bot_enabled && $pkg->bot_user_limit > 0)
                            <span class="text-xs text-gray-400">({{ $pkg->bot_user_limit }} guru)</span>
                        @elseif($pkg->bot_enabled)
                            <span class="text-xs text-gray-400">(Unlimited)</span>
                        @endif
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fas fa-history text-blue-400 w-4"></i>
                        Histori: <strong class="text-black dark:text-white">
                            {{ $pkg->history_quota_months ? $pkg->history_quota_months . ' bulan' : 'Selamanya' }}
                        </strong>
                    </li>
                </ul>
            </div>
        @endforeach
    </div>
    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400 text-center">
        <i class="fas fa-info-circle mr-1"></i>
        Untuk upgrade paket, silakan hubungi Administrator/Operator Anda.
    </p>
</div>
@endif

@endsection
