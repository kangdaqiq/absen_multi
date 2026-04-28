@extends('layouts.app')

@section('title', 'Backup & Restore Database')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        <i class="fas fa-database text-brand-500 mr-2"></i> Backup Database
    </h2>
</div>

<div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark mb-6">
    <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark flex justify-between items-center">
        <h3 class="font-medium text-black dark:text-white">
            Daftar Cadangan Database
        </h3>
        <form action="{{ route('backups.create') }}" method="POST">
            @csrf
            <button type="submit" class="inline-flex items-center justify-center gap-2.5 rounded-md bg-brand-500 px-6 py-2 text-center font-medium text-white hover:bg-opacity-90 transition">
                <i class="fas fa-download"></i> Backup Sekarang
            </button>
        </form>
    </div>
    
    <div class="p-6.5 pb-0">
        <div class="mb-6 flex w-full border-l-6 border-info bg-info/10 px-7 py-3 shadow-md">
            <p class="text-sm text-info">
                <strong>Info:</strong> Backup otomatis berjalan setiap hari pukul 02:00 AM. File disimpan di folder server
                <code class="bg-white/50 px-1 py-0.5 rounded">storage/app/backups</code>.
            </p>
        </div>
    </div>
    
    <div class="max-w-full overflow-x-auto pb-4">
        <table class="w-full table-auto">
            <thead>
                <tr class="bg-gray-2 text-left dark:bg-meta-4">
                    <th class="py-4 px-4 font-medium text-black dark:text-white text-center xl:pl-11" width="5%">No</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white">Nama File</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white text-center">Tanggal Backup</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white text-center">Ukuran</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white text-center" width="25%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($backups as $index => $file)
                    <tr>
                        <td class="border-b border-[#eee] py-5 px-4 pl-9 dark:border-strokedark xl:pl-11 text-center align-middle">
                            <p class="text-black dark:text-white">{{ $index + 1 }}</p>
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-middle">
                            <p class="text-black dark:text-white font-medium">{{ $file['name'] }}</p>
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center align-middle">
                            <p class="text-gray-500 dark:text-gray-400">{{ $file['date'] }}</p>
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center align-middle">
                            <p class="text-gray-500 dark:text-gray-400">{{ $file['size'] }}</p>
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center align-middle">
                            <div class="flex items-center justify-center space-x-2">
                                <a href="{{ route('backups.download', $file['name']) }}" class="inline-flex rounded-md bg-success px-3 py-1 text-sm font-medium text-white hover:bg-opacity-90" title="Download">
                                    <i class="fas fa-file-download mr-1 mt-1"></i> Download
                                </a>
                                <form action="{{ route('backups.delete', $file['name']) }}" method="POST" class="inline" onsubmit="return confirm('Hapus backup ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex rounded-md bg-danger px-3 py-1 text-sm font-medium text-white hover:bg-opacity-90" title="Hapus">
                                        <i class="fas fa-trash mr-1 mt-1"></i> Hapus
                                    </button>
                                </form>
                                <button type="button" class="inline-flex rounded-md bg-warning px-3 py-1 text-sm font-medium text-white hover:bg-opacity-90" onclick="alert('Untuk restore, silakan import file .sql ini via phpMyAdmin atau HeidiSQL.')">
                                    <i class="fas fa-undo mr-1 mt-1"></i> Restore Info
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center text-gray-500">
                            Belum ada file backup.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection