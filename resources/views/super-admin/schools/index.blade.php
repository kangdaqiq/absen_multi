@extends('layouts.app')

@section('title', 'Kelola Sekolah')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        <i class="fas fa-school text-brand-500 mr-2"></i> Kelola Sekolah
    </h2>
    <a href="{{ route('super-admin.schools.create') }}" class="inline-flex items-center justify-center gap-2.5 rounded-md bg-brand-500 px-10 py-3 text-center font-medium text-white hover:bg-opacity-90 lg:px-8 xl:px-10 transition">
        <i class="fas fa-plus"></i> Tambah Sekolah
    </a>
</div>

<div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
    <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
        <h3 class="font-medium text-black dark:text-white">
            Daftar Sekolah & Perkantoran
        </h3>
    </div>
    
    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto">
            <thead>
                <tr class="bg-gray-50 text-left dark:bg-gray-800/50 text-gray-800 dark:text-white/90 font-medium text-sm">
                    <th class="py-4 px-4 xl:pl-11 border-b border-stroke dark:border-strokedark">Nama Sekolah</th>
                    <th class="py-4 px-4 border-b border-stroke dark:border-strokedark">Kode</th>
                    <th class="py-4 px-4 border-b border-stroke dark:border-strokedark">Kontak</th>
                    <th class="py-4 px-4 text-center border-b border-stroke dark:border-strokedark">Siswa</th>
                    <th class="py-4 px-4 text-center border-b border-stroke dark:border-strokedark">Karyawan/Guru</th>
                    <th class="py-4 px-4 text-center border-b border-stroke dark:border-strokedark">Admin</th>
                    <th class="py-4 px-4 border-b border-stroke dark:border-strokedark">Status</th>
                    <th class="py-4 px-4 text-center border-b border-stroke dark:border-strokedark">Bot WA</th>
                    <th class="py-4 px-4 border-b border-stroke dark:border-strokedark">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse($schools as $school)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="border-b border-[#eee] py-5 px-4 pl-9 dark:border-strokedark xl:pl-11 align-top">
                            <p class="text-black dark:text-white font-medium">{{ $school->name }}</p>
                            @if($school->address)
                                <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">{{ Str::limit($school->address, 50) }}</p>
                            @endif
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-top">
                            <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $school->code }}</span>
                            <div class="mt-2">
                                @if($school->isOffice())
                                    <span class="inline-flex rounded-full bg-brand-500/10 px-3 py-1 text-xs font-medium text-brand-500"><i class="fas fa-building mr-1 mt-0.5"></i> Perkantoran</span>
                                @else
                                    <span class="inline-flex rounded-full bg-info/10 px-3 py-1 text-xs font-medium text-info"><i class="fas fa-school mr-1 mt-0.5"></i> Sekolah</span>
                                @endif
                            </div>
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-top text-sm">
                            <div class="flex flex-col gap-1 text-gray-600 dark:text-gray-400">
                                @if($school->phone)
                                    <span><i class="fas fa-phone mr-1"></i> {{ $school->phone }}</span>
                                @endif
                                @if($school->email)
                                    <span><i class="fas fa-envelope mr-1"></i> {{ $school->email }}</span>
                                @endif
                                @if($school->operator_phone)
                                    <span class="mt-1 text-brand-500"><i class="fas fa-headset mr-1"></i> <strong>Operator:</strong> {{ $school->operator_phone }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center align-top">
                            <p class="text-black dark:text-white font-medium">{{ $school->siswa_count }}</p>
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center align-top">
                            <p class="text-black dark:text-white font-medium">{{ $school->guru_count }}</p>
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center align-top">
                            <a href="{{ route('super-admin.schools.admins.index', $school) }}" class="inline-flex rounded-full bg-info/10 px-3 py-1 text-xs font-medium text-info hover:bg-info/20 transition">
                                {{ $school->admins_count }} admin
                            </a>
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-top">
                            @if($school->is_active)
                                <span class="inline-flex rounded-full bg-success-500/10 px-3 py-1 text-xs font-medium text-success-500">Aktif</span>
                            @else
                                <span class="inline-flex rounded-full bg-error-500/10 px-3 py-1 text-xs font-medium text-error-500">Nonaktif</span>
                            @endif
                        </td>
                        {{-- Toggle Bot WA --}}
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-top text-center">
                            @if($school->wa_enabled)
                                <label class="inline-flex items-center cursor-pointer" title="{{ $school->bot_enabled ? 'Klik untuk nonaktifkan bot' : 'Klik untuk aktifkan bot' }}">
                                    <input
                                        type="checkbox"
                                        class="sr-only bot-toggle"
                                        data-school-id="{{ $school->id }}"
                                        data-school-name="{{ $school->name }}"
                                        data-url="{{ route('super-admin.schools.toggle-bot', $school) }}"
                                        {{ $school->bot_enabled ? 'checked' : '' }}
                                    />
                                    <div class="bot-toggle-track relative w-11 h-6 rounded-full transition-colors duration-300 {{ $school->bot_enabled ? 'bg-brand-500' : 'bg-gray-300 dark:bg-meta-4' }}">
                                        <div class="bot-toggle-thumb absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-300" style="{{ $school->bot_enabled ? 'transform: translateX(20px);' : '' }}"></div>
                                    </div>
                                </label>
                                <p class="text-xs mt-1 {{ $school->bot_enabled ? 'text-brand-500' : 'text-gray-400 dark:text-gray-500' }}">
                                    {{ $school->bot_enabled ? 'Aktif' : 'Mati' }}
                                </p>
                            @else
                                <span class="text-xs text-gray-400 dark:text-gray-500 italic" title="WA tidak diaktifkan untuk sekolah ini">
                                    <i class="fas fa-ban"></i> N/A
                                </span>
                            @endif
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-top">
                            <div class="flex items-center space-x-3">
                                <a href="{{ route('super-admin.schools.edit', $school) }}" class="text-warning-500 hover:text-warning-700 hover:bg-warning-50 p-2 rounded-lg transition" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="{{ route('super-admin.schools.admins.index', $school) }}" class="text-info-500 hover:text-info-700 hover:bg-info-50 p-2 rounded-lg transition" title="Kelola Admin">
                                    <i class="fas fa-users-cog"></i>
                                </a>
                                <a href="{{ route('super-admin.schools.devices.index', $school) }}" class="text-theme-purple-500 hover:text-theme-purple-700 hover:bg-theme-purple-500/10 p-2 rounded-lg transition" title="Kelola Device">
                                    <i class="fas fa-microchip"></i>
                                </a>
                                <button type="button" class="text-error-500 hover:text-error-700 hover:bg-error-50 p-2 rounded-lg transition btnDelete" data-id="{{ $school->id }}" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <form id="delete-form-{{ $school->id }}" action="{{ route('super-admin.schools.destroy', $school) }}" method="POST" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center">
                            Belum ada sekolah
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($schools->hasPages())
        <div class="px-5 py-4 border-t border-stroke dark:border-strokedark">
            {{ $schools->links('vendor.pagination.tailwind') }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ── Delete School ──────────────────────────────────────────────
            document.querySelectorAll('.btnDelete').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    if (confirm('Apakah Anda yakin ingin menghapus sekolah ini? Semua data terkait akan ikut terhapus!')) {
                        document.getElementById('delete-form-' + id).submit();
                    }
                });
            });

            // ── Toggle Bot WA (AJAX) ───────────────────────────────────────
            document.querySelectorAll('.bot-toggle').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const url          = this.getAttribute('data-url');
                    const schoolName   = this.getAttribute('data-school-name');
                    const isChecked    = this.checked;
                    const label        = this.closest('label');
                    const track        = label.querySelector('.bot-toggle-track');
                    const thumb        = label.querySelector('.bot-toggle-thumb');
                    const statusText   = label.closest('td').querySelector('p');
                    const csrfToken    = document.querySelector('meta[name="csrf-token"]').content;

                    // Disable toggle selama proses
                    this.disabled = true;

                    fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const active = data.bot_enabled;

                            // Update visual
                            if (active) {
                                track.classList.remove('bg-gray-300', 'dark:bg-meta-4');
                                track.classList.add('bg-brand-500');
                                thumb.style.transform = 'translateX(20px)';
                                statusText.textContent = 'Aktif';
                                statusText.className = 'text-xs mt-1 text-brand-500';
                            } else {
                                track.classList.remove('bg-brand-500');
                                track.classList.add('bg-gray-300', 'dark:bg-meta-4');
                                thumb.style.transform = 'translateX(0)';
                                statusText.textContent = 'Mati';
                                statusText.className = 'text-xs mt-1 text-gray-400 dark:text-gray-500';
                            }

                            // Toast notifikasi singkat
                            showToast(data.message, active ? 'success' : 'warning');
                        } else {
                            // Rollback jika gagal
                            this.checked = !isChecked;
                            showToast('Gagal mengubah status bot.', 'error');
                        }
                    })
                    .catch(() => {
                        this.checked = !isChecked;
                        showToast('Terjadi kesalahan jaringan.', 'error');
                    })
                    .finally(() => {
                        this.disabled = false;
                    });
                });
            });

            // ── Toast helper ───────────────────────────────────────────────
            function showToast(message, type) {
                const colors = {
                    success: '#10B981',
                    warning: '#F59E0B',
                    error:   '#EF4444',
                };
                const toast = document.createElement('div');
                toast.textContent = message;
                toast.style.cssText = `
                    position: fixed; bottom: 24px; right: 24px; z-index: 9999;
                    background: ${colors[type] || colors.success};
                    color: #fff; padding: 12px 20px; border-radius: 8px;
                    font-size: 13px; font-weight: 500;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.18);
                    opacity: 0; transition: opacity 0.3s ease;
                    max-width: 360px; line-height: 1.4;
                `;
                document.body.appendChild(toast);
                requestAnimationFrame(() => toast.style.opacity = '1');
                setTimeout(() => {
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }
        });
    </script>
@endpush