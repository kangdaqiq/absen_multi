@extends('layouts.app')

@section('title', 'Paket Langganan')

@section('content')
    <div x-data="{
        showModal: false,
        selectedPackageId: '{{ $pendingSubscription?->package_id ?? ($activeSubscription?->package_id ?? ($packages->first()?->id ?? '')) }}',
        billingCycle: '{{ $pendingSubscription?->billing_cycle ?? 'monthly' }}',
        isSubmitting: false,
        packages: @js($packages->map(fn($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'price_monthly' => (float)$p->price_monthly,
            'price_yearly' => (float)$p->price_yearly,
            'student_limit' => $p->student_limit,
            'teacher_limit' => $p->teacher_limit,
            'wa_enabled' => (bool)$p->wa_enabled,
            'bot_enabled' => (bool)$p->bot_enabled,
            'bot_user_limit' => $p->bot_user_limit,
            'history_quota_months' => $p->history_quota_months
        ])),
        get selectedPackage() {
            return this.packages.find(p => p.id == this.selectedPackageId) || this.packages[0];
        },
        get currentPrice() {
            if (!this.selectedPackage) return 0;
            return this.billingCycle === 'yearly' ? this.selectedPackage.price_yearly : this.selectedPackage.price_monthly;
        },
        openRenewModal(pkgId = null, cycle = null) {
            if (pkgId) this.selectedPackageId = pkgId;
            if (cycle) this.billingCycle = cycle;
            this.showModal = true;
        },
        getWaLink() {
            if (!this.selectedPackage) return '#';
            let phone = '{{ env('DEVELOPER_WA', '6281327735093') }}';
            let cycleText = this.billingCycle === 'yearly' ? 'Tahunan (1 Tahun)' : 'Bulanan (1 Bulan)';
            let text = `Halo, saya admin dari sekolah *{{ $school->name }}*.%0A%0ASaya ingin memperpanjang paket *${this.selectedPackage.name}* (${cycleText}).%0ABerikut bukti transfer saya:`;
            return `https://wa.me/${phone}?text=${text}`;
        },
        async confirmManualAndRedirect() {
            if (!this.selectedPackage || this.isSubmitting) return;
            this.isSubmitting = true;
            try {
                const response = await fetch('{{ route('subscription.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content
                    },
                    body: JSON.stringify({
                        package_id: this.selectedPackage.id,
                        billing_cycle: this.billingCycle,
                        payment_method: 'manual'
                    })
                });
                const data = await response.json();
                if (data.success) {
                    window.open(this.getWaLink(), '_blank');
                    window.location.reload();
                } else {
                    alert(data.message || 'Terjadi kesalahan.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Gagal memproses permintaan.');
            } finally {
                this.isSubmitting = false;
            }
        }
    }">

        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
                <i class="fas fa-box-open text-brand-500 mr-2"></i> Paket Langganan
            </h2>
            <div class="flex flex-wrap items-center gap-3">
                @if($isSelfHosted)
                    @if(!empty($licenseInfo['expired_at']) && $licenseInfo['expired_at'] !== 'Selamanya')
                        @php 
                            $licExpiredAt = \Carbon\Carbon::parse($licenseInfo['expired_at']); 
                        @endphp
                        @if($licExpiredAt->isPast())
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-3 py-1 text-sm font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse inline-block"></span>
                                Lisensi Kedaluwarsa {{ $licExpiredAt->diffForHumans() }}
                            </span>
                        @elseif(now()->addDays(7)->greaterThanOrEqualTo($licExpiredAt))
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-yellow-100 px-3 py-1 text-sm font-medium text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                                <span class="w-2 h-2 rounded-full bg-yellow-500 animate-pulse inline-block"></span>
                                Lisensi Kedaluwarsa {{ $licExpiredAt->diffForHumans() }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>
                                Lisensi Aktif sampai {{ $licExpiredAt->format('d M Y') }}
                            </span>
                        @endif
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                            <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>
                            Lisensi Aktif (Selamanya)
                        </span>
                    @endif
                @else
                    @if($school->expired_at)
                        @php $expiredAt = $school->expired_at; @endphp
                        @if($expiredAt->isPast())
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-3 py-1 text-sm font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse inline-block"></span>
                                Langganan Kedaluwarsa {{ $expiredAt->diffForHumans() }}
                            </span>
                        @elseif(now()->addDays(7)->greaterThanOrEqualTo($expiredAt))
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
                @endif

                <button @click="openRenewModal()"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600 transition shadow-sm">
                    <i class="fas fa-bolt"></i> Perpanjang / Bayar Paket
                </button>
            </div>
        </div>

        {{-- ── Status Aktif & Penggunaan ────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
            {{-- Paket Aktif / Lisensi --}}
            <div class="rounded-2xl border border-stroke bg-white p-5 shadow-sm dark:border-strokedark dark:bg-boxdark col-span-1 sm:col-span-2">
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-brand-500/10">
                        <i class="fas fa-key text-2xl text-brand-500"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-0.5">Paket / Lisensi Aktif</p>
                        @if($isSelfHosted)
                            <h3 class="text-xl font-bold text-black dark:text-white truncate">
                                Lisensi Self-Hosted
                            </h3>
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                <span class="rounded-full bg-green-500/10 text-green-600 px-2 py-0.5 font-medium">
                                    Klien: {{ $licenseInfo['client_name'] ?? 'SEKOLAH' }}
                                </span>
                                <span class="text-gray-500 dark:text-gray-400">
                                    Masa Berlaku: {{ $licenseInfo['expired_at'] ?? 'Selamanya' }}
                                </span>
                            </div>
                        @else
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
                                <p class="mt-1 text-xs text-gray-400">Belum ada langganan aktif. Klik perpanjang untuk aktivasi paket.</p>
                            @endif
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
                    <span class="text-sm font-normal text-gray-400">/
                        {{ $usage['students']['limit'] > 0 ? number_format($usage['students']['limit']) : '∞' }}</span>
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
                    <span class="text-sm font-normal text-gray-400">/
                        {{ $usage['teachers']['limit'] > 0 ? number_format($usage['teachers']['limit']) : '∞' }}</span>
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
                                    @if($isSelfHosted)
                                        @if(isset($licenseInfo['history_quota_months']) && $licenseInfo['history_quota_months'] > 0)
                                            {{ $licenseInfo['history_quota_months'] }} bulan terakhir
                                        @else
                                            Tidak Terbatas (Simpan Selamanya)
                                        @endif
                                    @else
                                        @if($school->history_quota_months)
                                            {{ $school->history_quota_months }} bulan terakhir
                                        @else
                                            Tidak Terbatas (Simpan Selamanya)
                                        @endif
                                    @endif
                                </p>
                            </div>
                        </li>
                    </ul>
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
                                            <th class="px-6 py-3 font-semibold text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Metode</th>
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
                                                    <span class="inline-flex items-center gap-1 uppercase text-xs font-semibold">
                                                        @if($sub->payment_method === 'qris')
                                                            <i class="fas fa-qrcode text-brand-500"></i> QRIS
                                                        @else
                                                            <i class="fas fa-money-bill-wave text-blue-500"></i> {{ $sub->payment_method ?? 'Manual' }}
                                                        @endif
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                                                    {{ $sub->expired_at?->format('d M Y') ?? '∞' }}
                                                </td>
                                                <td class="px-6 py-4">
                                                    @if($sub->status === 'paid')
                                                        <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                                            Aktif / Lunas
                                                        </span>
                                                    @elseif($sub->status === 'unpaid')
                                                        <button @click="openRenewModal({{ $sub->package_id }}, '{{ $sub->billing_cycle }}')" class="inline-flex items-center gap-1 rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-700 hover:bg-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-400 transition">
                                                            <i class="fas fa-clock"></i> Belum Dibayar
                                                        </button>
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
                <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <h3 class="text-lg font-semibold text-black dark:text-white">
                            <i class="fas fa-tags text-brand-500 mr-2"></i> Pilihan Paket Langganan
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Pilih paket yang sesuai untuk perpanjang masa aktif sekolah Anda.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($packages as $pkg)
                        @php $isActive = $activeSubscription?->package_id === $pkg->id; @endphp
                        <div class="relative flex flex-col justify-between rounded-2xl border {{ $isActive ? 'border-brand-500 ring-2 ring-brand-500/20' : 'border-stroke dark:border-strokedark' }} bg-white p-6 shadow-sm dark:bg-boxdark transition hover:shadow-md">
                            <div>
                                @if($isActive)
                                    <span class="absolute top-4 right-4 inline-flex rounded-full bg-brand-500 px-2.5 py-0.5 text-xs font-semibold text-white">
                                        Paket Aktif
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
                                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400 mb-6">
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

                            <button @click="openRenewModal({{ $pkg->id }})" class="w-full inline-flex items-center justify-center gap-2 rounded-xl {{ $isActive ? 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-800 dark:text-white' : 'bg-brand-500 text-white hover:bg-brand-600 shadow-sm' }} py-2.5 px-4 text-sm font-semibold transition">
                                <i class="fas fa-credit-card"></i> {{ $isActive ? 'Perpanjang Paket Ini' : 'Pilih & Perpanjang' }}
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ── Modal Pembayaran Manual & WA ───────────────────────────────── --}}
        <div x-show="showModal" style="display: none;"
            class="fixed inset-0 z-99999 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 overflow-y-auto">
            <div x-show="showModal" @click.outside="showModal = false"
                class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl dark:bg-boxdark my-8"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                <div class="mb-4 flex items-center justify-between border-b border-stroke pb-3 dark:border-strokedark">
                    <div>
                        <h3 class="text-lg font-bold text-black dark:text-white flex items-center gap-2">
                            <i class="fas fa-wallet text-brand-500"></i> Perpanjangan Langganan
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Pilih paket dan lakukan pembayaran via transfer bank</p>
                    </div>
                    <button @click="showModal = false" class="text-gray-400 hover:text-black dark:hover:text-white transition">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>

                <div class="space-y-4 text-sm text-gray-600 dark:text-gray-400">
                    <div class="space-y-3 rounded-xl border border-stroke bg-gray-50 p-4 dark:border-strokedark dark:bg-gray-800">
                        {{-- Pilih Paket --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Pilih Paket:</label>
                            <select x-model="selectedPackageId" class="w-full rounded-lg border border-stroke bg-white px-3 py-2 text-xs font-medium text-black outline-none focus:border-brand-500 dark:border-strokedark dark:bg-boxdark dark:text-white">
                                <template x-for="p in packages" :key="p.id">
                                    <option :value="p.id" x-text="p.name + ' - Rp ' + Number(p.price_monthly).toLocaleString('id-ID') + '/bln'"></option>
                                </template>
                            </select>
                        </div>

                        {{-- Pilih Durasi --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Siklus Pembayaran:</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" @click="billingCycle = 'monthly'"
                                    :class="billingCycle === 'monthly' ? 'border-brand-500 bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400 font-bold' : 'border-stroke bg-white text-gray-700 dark:border-strokedark dark:bg-boxdark dark:text-gray-300'"
                                    class="rounded-lg border p-2.5 text-left text-xs transition">
                                    <p class="font-medium">Bulanan (1 Bulan)</p>
                                    <p class="text-[11px] text-gray-500" x-text="'Rp ' + Number(selectedPackage?.price_monthly || 0).toLocaleString('id-ID')"></p>
                                </button>
                                <button type="button" @click="billingCycle = 'yearly'"
                                    :class="billingCycle === 'yearly' ? 'border-brand-500 bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400 font-bold' : 'border-stroke bg-white text-gray-700 dark:border-strokedark dark:bg-boxdark dark:text-gray-300'"
                                    class="rounded-lg border p-2.5 text-left text-xs transition relative">
                                    <span class="absolute -top-2 right-2 rounded-full bg-green-500 px-1.5 py-0.2 text-[9px] font-bold text-white uppercase">Hemat</span>
                                    <p class="font-medium">Tahunan (1 Tahun)</p>
                                    <p class="text-[11px] text-gray-500" x-text="'Rp ' + Number(selectedPackage?.price_yearly || 0).toLocaleString('id-ID')"></p>
                                </button>
                            </div>
                        </div>

                        <div class="border-t border-stroke pt-2 flex justify-between items-center text-xs dark:border-strokedark">
                            <span class="text-gray-500">Nominal Transfer:</span>
                            <span class="text-sm font-extrabold text-brand-600 dark:text-brand-400" x-text="'Rp ' + Number(currentPrice).toLocaleString('id-ID')"></span>
                        </div>
                    </div>

                    <div class="rounded-xl bg-gray-50 p-3.5 dark:bg-gray-800">
                        <p class="mb-2 text-xs font-semibold text-black dark:text-white">Daftar Rekening Pembayaran Developer:</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                            <div class="flex items-center gap-2.5 rounded-lg border border-stroke bg-white p-2.5 dark:border-strokedark dark:bg-boxdark">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600 text-xs">
                                    <i class="fas fa-university"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-bold text-black dark:text-white">{{ env('DEVELOPER_BANK_1_NAME', 'BCA') }}</p>
                                    <p class="font-mono font-bold text-brand-500 truncate">{{ env('DEVELOPER_BANK_1_ACCOUNT', '1234567890') }}</p>
                                    <p class="text-[10px] text-gray-500 truncate">a.n. {{ env('DEVELOPER_BANK_1_OWNER', 'Developer') }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2.5 rounded-lg border border-stroke bg-white p-2.5 dark:border-strokedark dark:bg-boxdark">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-orange-100 text-orange-600 text-xs">
                                    <i class="fas fa-university"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-bold text-black dark:text-white">{{ env('DEVELOPER_BANK_2_NAME', 'BNI') }}</p>
                                    <p class="font-mono font-bold text-brand-500 truncate">{{ env('DEVELOPER_BANK_2_ACCOUNT', '1234567890') }}</p>
                                    <p class="text-[10px] text-gray-500 truncate">a.n. {{ env('DEVELOPER_BANK_2_OWNER', 'Developer') }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2.5 rounded-lg border border-stroke bg-white p-2.5 dark:border-strokedark dark:bg-boxdark">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-orange-100 text-orange-600 text-xs">
                                    <i class="fas fa-university"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-bold text-black dark:text-white">{{ env('DEVELOPER_BANK_3_NAME', 'Seabank') }}</p>
                                    <p class="font-mono font-bold text-brand-500 truncate">{{ env('DEVELOPER_BANK_3_ACCOUNT', '1234567890') }}</p>
                                    <p class="text-[10px] text-gray-500 truncate">a.n. {{ env('DEVELOPER_BANK_3_OWNER', 'Developer') }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2.5 rounded-lg border border-stroke bg-white p-2.5 dark:border-strokedark dark:bg-boxdark">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600 text-xs">
                                    <i class="fas fa-wallet"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-bold text-black dark:text-white">{{ env('DEVELOPER_BANK_4_NAME', 'Dana') }}</p>
                                    <p class="font-mono font-bold text-brand-500 truncate">{{ env('DEVELOPER_BANK_4_ACCOUNT', '081234567890') }}</p>
                                    <p class="text-[10px] text-gray-500 truncate">a.n. {{ env('DEVELOPER_BANK_4_OWNER', 'Developer') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button @click="confirmManualAndRedirect()" :disabled="isSubmitting"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-green-500 py-3 text-sm font-semibold text-white hover:bg-green-600 disabled:opacity-50 transition shadow-sm">
                        <i class="fab fa-whatsapp" x-show="!isSubmitting"></i>
                        <i class="fas fa-spinner fa-spin" x-show="isSubmitting" style="display: none;"></i>
                        <span x-text="isSubmitting ? 'Memproses...' : 'Kirim Bukti Transfer via WhatsApp'"></span>
                    </button>
                </div>

                <div class="mt-4 pt-3 border-t border-stroke dark:border-strokedark flex justify-end">
                    <button @click="showModal = false" class="rounded-lg border border-stroke px-4 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-strokedark dark:text-white dark:hover:bg-gray-800 transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection