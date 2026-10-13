@props([
    'title' => 'Fitur Terkunci',
    'featureName' => 'Scanner Wajah (Face ID)',
    'description' => 'Fitur ini belum diaktifkan untuk sekolah ini. Hubungi Admin untuk mengaktifkan lisensi atau modul ini.',
    'school' => null,
    'icon' => 'fas fa-camera',
])

@php
    $user = auth()->user();
    $schoolObj = $school ?? ($user?->school ?? null);
    $schoolName = $schoolObj?->name ?? 'Sekolah';
    
    // Nomor kontak SuperAdmin / CS WhatsApp jika tersedia dari config atau setting
    $superadminPhone = config('services.whatsapp.superadmin_phone', env('SUPERADMIN_WA_PHONE', '6281234567890'));
    // Bersihkan nomor HP
    $cleanPhone = preg_replace('/[^0-9]/', '', $superadminPhone);
    if (str_starts_with($cleanPhone, '0')) {
        $cleanPhone = '62' . substr($cleanPhone, 1);
    }
    
    $waMessage = urlencode("Halo Admin, saya dari *{$schoolName}* ingin mengajukan aktivasi fitur *{$featureName}* pada sistem absensi.");
    $waUrl = "https://wa.me/{$cleanPhone}?text={$waMessage}";
@endphp

<style>
    .lock-overlay-backdrop {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        width: 100%;
        min-height: 100%;
        z-index: 30;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding: 3rem 1rem 2.5rem 1rem;
        background: rgba(15, 23, 42, 0.45);
        backdrop-filter: blur(3px);
        -webkit-backdrop-filter: blur(3px);
        box-sizing: border-box;
        border-radius: 1.25rem;
        pointer-events: auto;
    }

    .dark .lock-overlay-backdrop {
        background: rgba(15, 23, 42, 0.65);
    }

    @media (max-width: 640px) {
        .lock-overlay-backdrop {
            padding: 1.5rem 0.75rem 2rem 0.75rem;
        }
    }

    .lock-overlay-card {
        position: sticky;
        top: 4.5rem;
        width: 100%;
        max-width: 520px;
        background-color: #ffffff;
        border-radius: 1.5rem;
        padding: 2rem 1.75rem;
        text-align: center;
        box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.35);
        border: 1px solid rgba(226, 232, 240, 0.9);
        box-sizing: border-box;
        overflow: hidden;
        animation: lockCardPop 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        pointer-events: auto;
        z-index: 31;
    }

    @media (max-width: 640px) {
        .lock-overlay-card {
            top: 1.5rem;
            padding: 1.5rem 1.25rem;
            border-radius: 1.25rem;
        }
    }

    .dark .lock-overlay-card {
        background-color: #171f2e;
        border-color: rgba(51, 65, 85, 0.9);
        box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.7);
    }

    @keyframes lockCardPop {
        0% {
            opacity: 0;
            transform: scale(0.92) translateY(12px);
        }
        100% {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    .lock-badge-wrapper {
        position: relative;
        width: 4.5rem;
        height: 4.5rem;
        margin: 0 auto 1.25rem auto;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .lock-badge-icon-box {
        width: 4rem;
        height: 4rem;
        border-radius: 1.25rem;
        background: linear-gradient(135deg, #4f6bed, #6366f1);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 1.5rem;
        box-shadow: 0 10px 25px rgba(79, 107, 237, 0.35);
        position: relative;
    }

    .lock-badge-mini {
        position: absolute;
        bottom: -4px;
        right: -4px;
        width: 1.75rem;
        height: 1.75rem;
        border-radius: 9999px;
        background-color: #f59e0b;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        border: 2px solid #ffffff;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
    }
    .dark .lock-badge-mini {
        border-color: #171f2e;
    }

    .lock-pill-label {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        background-color: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
        margin-bottom: 0.75rem;
    }
    .dark .lock-pill-label {
        background-color: rgba(245, 158, 11, 0.15);
        color: #fbbf24;
        border-color: rgba(245, 158, 11, 0.3);
    }

    .lock-overlay-title {
        font-size: 1.375rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 0.5rem 0;
        line-height: 1.3;
    }
    .dark .lock-overlay-title {
        color: #f8fafc;
    }

    .lock-overlay-desc {
        font-size: 0.8125rem;
        color: #64748b;
        line-height: 1.5;
        margin: 0 0 1.25rem 0;
    }
    .dark .lock-overlay-desc {
        color: #94a3b8;
    }

    .lock-info-box {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        padding: 1rem 1.125rem;
        text-align: left;
        margin-bottom: 1.5rem;
        box-sizing: border-box;
    }
    .dark .lock-info-box {
        background-color: #1e293b;
        border-color: #334155;
    }

    .lock-info-box-title {
        font-size: 0.6875rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #64748b;
        margin: 0 0 0.625rem 0;
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }
    .dark .lock-info-box-title {
        color: #94a3b8;
    }

    .lock-info-list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .lock-info-item {
        display: flex;
        align-items: flex-start;
        gap: 0.625rem;
        font-size: 0.75rem;
        line-height: 1.45;
        color: #334155;
    }
    .dark .lock-info-item {
        color: #cbd5e1;
    }

    .lock-number-badge {
        flex-shrink: 0;
        width: 1.125rem;
        height: 1.125rem;
        border-radius: 9999px;
        background-color: #e0e7ff;
        color: #3730a3;
        font-size: 0.6875rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-top: 0.05rem;
    }
    .dark .lock-number-badge {
        background-color: #312e81;
        color: #c7d2fe;
    }

    .lock-actions-row {
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
        align-items: stretch;
    }
    @media (min-width: 480px) {
        .lock-actions-row {
            flex-direction: row;
            align-items: center;
            justify-content: center;
        }
    }

    .lock-btn-whatsapp {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        border-radius: 0.875rem;
        font-size: 0.8125rem;
        font-weight: 700;
        color: #ffffff !important;
        background: linear-gradient(135deg, #10b981, #059669);
        box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
        text-decoration: none !important;
        transition: all 0.2s ease;
        flex: 1;
    }
    .lock-btn-whatsapp:hover {
        background: linear-gradient(135deg, #059669, #047857);
        box-shadow: 0 6px 18px rgba(16, 185, 129, 0.45);
        transform: translateY(-1px);
    }

    .lock-btn-back {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        border-radius: 0.875rem;
        font-size: 0.8125rem;
        font-weight: 600;
        color: #334155;
        background-color: #f1f5f9;
        border: 1px solid #e2e8f0;
        text-decoration: none !important;
        transition: all 0.2s ease;
    }
    .lock-btn-back:hover {
        background-color: #e2e8f0;
        color: #0f172a;
    }
    .dark .lock-btn-back {
        background-color: #1e293b;
        color: #cbd5e1;
        border-color: #334155;
    }
    .dark .lock-btn-back:hover {
        background-color: #334155;
        color: #f8fafc;
    }

    .lock-admin-shortcut {
        margin-top: 0.75rem;
        padding-top: 0.625rem;
        border-top: 1px dashed #cbd5e1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0.6875rem;
    }
    .dark .lock-admin-shortcut {
        border-top-color: #475569;
    }
    .lock-admin-shortcut a {
        color: #4f6bed;
        font-weight: 700;
        text-decoration: underline;
    }
    .lock-admin-shortcut a:hover {
        color: #3b50c4;
    }
</style>

<div class="lock-overlay-backdrop">
    <div class="lock-overlay-card">
        
        <!-- Lock & Feature Badge -->
        <div class="lock-badge-wrapper">
            <div class="lock-badge-icon-box">
                <i class="{{ $icon }}"></i>
                <div class="lock-badge-mini">
                    <i class="fas fa-lock"></i>
                </div>
            </div>
        </div>

        <!-- Category Badge -->
        <div>
            <span class="lock-pill-label">
                <i class="fas fa-gem" style="font-size: 0.625rem;"></i>
                Fitur Tambahan / Add-on
            </span>
        </div>

        <!-- Title -->
        <h3 class="lock-overlay-title">
            {{ $title }}
        </h3>

        <!-- Description -->
        <p class="lock-overlay-desc">
            {{ $description }}
        </p>

        <!-- Information Card -->
        <div class="lock-info-box">
            <div class="lock-info-box-title">
                <i class="fas fa-info-circle" style="color: #4f6bed;"></i>
                Cara Membuka Kunci Fitur Ini:
            </div>
            <ul class="lock-info-list">
                <li class="lock-info-item">
                    <span class="lock-number-badge">1</span>
                    <span>Hubungi <strong>Admin</strong> sistem untuk mengaktifkan modul <strong>{{ $featureName }}</strong> untuk <em>{{ $schoolName }}</em>.</span>
                </li>
                <li class="lock-info-item">
                    <span class="lock-number-badge">2</span>
                    <span>Atau lakukan upgrade paket langganan sekolah yang mencakup lisensi Face Recognition.</span>
                </li>
            </ul>

            @if($user?->isSuperAdmin())
                <div class="lock-admin-shortcut">
                    <span style="color: #64748b;"><i class="fas fa-shield-alt mr-1"></i> Mode Admin:</span>
                    <a href="{{ route('super-admin.schools.edit', $schoolObj ?? 1) }}">Aktifkan di Pengaturan Sekolah &rarr;</a>
                </div>
            @endif
        </div>

        <!-- Action Buttons -->
        <div class="lock-actions-row">
            <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer" class="lock-btn-whatsapp">
                <i class="fab fa-whatsapp" style="font-size: 1.125rem;"></i>
                <span>Hubungi Admin</span>
            </a>

            <a href="{{ route('dashboard') }}" class="lock-btn-back">
                <i class="fas fa-arrow-left" style="font-size: 0.6875rem;"></i>
                <span>Dashboard</span>
            </a>
        </div>
    </div>
</div>
