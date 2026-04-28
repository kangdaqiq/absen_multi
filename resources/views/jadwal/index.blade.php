@extends('layouts.app')

@section('title', 'Manajemen Jadwal')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        <i class="fas fa-clock text-brand-500 mr-2"></i> Manajemen Jadwal
    </h2>
</div>

<div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
    <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
        <h3 class="font-medium text-black dark:text-white">
            Atur Jadwal Masuk & Pulang (Mingguan)
        </h3>
    </div>
    
    <div class="p-6.5">
        @if(session('success'))
            <div class="mb-6 flex w-full border-l-6 border-success bg-success-50 px-7 py-4 shadow-md dark:bg-[#1B1B24] dark:bg-opacity-30">
                <div class="mr-5 flex h-9 w-full max-w-[36px] items-center justify-center rounded-lg bg-success text-white">
                    <i class="fas fa-check"></i>
                </div>
                <div class="w-full">
                    <h5 class="mb-1 font-semibold text-[#004434] dark:text-[#34D399]">Berhasil</h5>
                    <p class="text-[#004434] dark:text-[#34D399]">{{ session('success') }}</p>
                </div>
            </div>
        @endif
        
        @if(session('error'))
            <div class="mb-6 flex w-full border-l-6 border-error bg-error-50 px-7 py-4 shadow-md dark:bg-[#1B1B24] dark:bg-opacity-30">
                <div class="mr-5 flex h-9 w-full max-w-[36px] items-center justify-center rounded-lg bg-error text-white">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="w-full">
                    <h5 class="mb-1 font-semibold text-[#B45454] dark:text-[#F87171]">Gagal</h5>
                    <p class="text-[#B45454] dark:text-[#F87171]">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        <form action="{{ route('jadwal.update-all') }}" method="POST">
            @csrf
            
            <div class="max-w-full overflow-x-auto mb-6">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-2 text-left dark:bg-meta-4">
                            <th class="py-4 px-4 font-medium text-black dark:text-white xl:pl-11" style="width: 15%">Hari</th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white" style="width: 25%">Jam Masuk</th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white" style="width: 25%">Jam Pulang</th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white" style="width: 20%">Toleransi (Menit)</th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white text-center" style="width: 15%">Aktif?</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $days = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
                            $existingJadwal = $jadwal->keyBy('index_hari');
                        @endphp

                        @foreach($days as $index => $dayName)
                            @php
                                $current = $existingJadwal->get($index);
                            @endphp
                            <tr>
                                <td class="border-b border-[#eee] py-5 px-4 pl-9 dark:border-strokedark xl:pl-11">
                                    <p class="text-black dark:text-white font-medium">{{ $dayName }}</p>
                                </td>
                                <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark">
                                    <input type="time" name="schedules[{{ $index }}][jam_masuk]" 
                                        class="w-full rounded border border-stroke bg-transparent py-2.5 px-4 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 text-black dark:text-white" 
                                        value="{{ $current ? substr($current->jam_masuk, 0, 5) : '07:00' }}">
                                </td>
                                <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark">
                                    <input type="time" name="schedules[{{ $index }}][jam_pulang]" 
                                        class="w-full rounded border border-stroke bg-transparent py-2.5 px-4 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 text-black dark:text-white" 
                                        value="{{ $current ? substr($current->jam_pulang, 0, 5) : '15:00' }}">
                                </td>
                                <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark">
                                    <input type="number" name="schedules[{{ $index }}][toleransi]" 
                                        class="w-full rounded border border-stroke bg-transparent py-2.5 px-4 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 text-black dark:text-white" 
                                        value="{{ $current ? $current->toleransi : 15 }}" min="0">
                                </td>
                                <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center">
                                    <!-- Toggle Switch -->
                                    <label for="activeSwitch{{ $index }}" class="flex cursor-pointer select-none items-center justify-center">
                                        <div class="relative">
                                            <input type="checkbox" id="activeSwitch{{ $index }}" name="schedules[{{ $index }}][is_active]" value="1" class="sr-only peer" {{ ($current && $current->is_active) ? 'checked' : '' }} />
                                            <div class="block h-8 w-14 rounded-full bg-gray-300 dark:bg-gray-600 peer-checked:bg-brand-500 transition-colors"></div>
                                            <div class="dot absolute left-1 top-1 h-6 w-6 rounded-full bg-white transition-transform peer-checked:translate-x-6 shadow-sm"></div>
                                        </div>
                                    </label>
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end gap-4.5 mt-6">
                <button type="submit" class="flex items-center justify-center gap-2 rounded bg-brand-500 py-3 px-6 font-medium text-white hover:bg-opacity-90 transition">
                    <i class="fas fa-save"></i> Simpan Semua Jadwal
                </button>
            </div>
        </form>
    </div>
</div>

@endsection