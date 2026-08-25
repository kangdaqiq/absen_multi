<?php

namespace App\Exports;

use App\Models\AbsensiGuru;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RekapGuruExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $startDate;
    protected $endDate;
    protected $guruId;
    protected $shiftId;

    public function __construct($startDate, $endDate, $guruId = null, $shiftId = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->guruId = $guruId;
        $this->shiftId = $shiftId;
    }

    public function collection()
    {
        $query = AbsensiGuru::with(['guru', 'shift', 'jadwal.mapel', 'jadwal.kelas'])
            ->whereBetween('tanggal', [$this->startDate, $this->endDate])
            ->orderBy('tanggal', 'asc')
            ->orderBy('jam_masuk', 'asc');

        if ($this->guruId) {
            $query->where('guru_id', $this->guruId);
        }

        if ($this->shiftId) {
            $query->where('shift_id', $this->shiftId);
        }

        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            $query->where('school_id', auth()->user()->school_id);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Nama Guru / Staff',
            'NIP',
            'Shift',
            'Status',
            'Jam Masuk',
            'Jam Pulang',
            'Terlambat (Menit)',
            'Keterangan'
        ];
    }

    public function map($absensi): array
    {
        $shiftName = $absensi->shift ? $absensi->shift->nama_shift : '-';

        return [
            \Carbon\Carbon::parse($absensi->tanggal)->format('d/m/Y'),
            $absensi->guru->nama ?? '-',
            $absensi->guru->nip ?? '-',
            $shiftName,
            $absensi->status,
            $absensi->jam_masuk ? \Carbon\Carbon::parse($absensi->jam_masuk)->format('H:i') : '-',
            $absensi->jam_pulang ? \Carbon\Carbon::parse($absensi->jam_pulang)->format('H:i') : '-',
            $absensi->menit_terlambat > 0 ? $absensi->menit_terlambat : '0',
            $absensi->keterangan ?? '-'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
