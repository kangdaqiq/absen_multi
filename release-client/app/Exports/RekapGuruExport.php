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

    public function __construct($startDate, $endDate, $guruId = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->guruId = $guruId;
    }

    public function collection()
    {
        $query = AbsensiGuru::with(['guru', 'jadwal.mapel', 'jadwal.kelas'])
            ->whereBetween('tanggal', [$this->startDate, $this->endDate])
            ->orderBy('tanggal', 'asc')
            ->orderBy('waktu_hadir', 'asc');

        if ($this->guruId) {
            $query->where('guru_id', $this->guruId);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Guru',
            'Mata Pelajaran',
            'Kelas',
            'Jam Mengajar',
            'Status',
            'Waktu Hadir',
            'Keterangan'
        ];
    }

    public function map($absensi): array
    {
        $jamMengajar = '-';
        if ($absensi->jadwal) {
            $jamMulai = substr($absensi->jadwal->jam_mulai, 0, 5);
            $jamSelesai = substr($absensi->jadwal->jam_selesai, 0, 5);
            $jamMengajar = "{$jamMulai} - {$jamSelesai}";
        }

        return [
            \Carbon\Carbon::parse($absensi->tanggal)->format('d/m/Y'),
            $absensi->guru->nama ?? '-',
            $absensi->jadwal->mapel->nama_mapel ?? '-',
            $absensi->jadwal->kelas->nama_kelas ?? '-',
            $jamMengajar,
            $absensi->status,
            $absensi->waktu_hadir ? \Carbon\Carbon::parse($absensi->waktu_hadir)->format('H:i') : '-',
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
