@extends('layouts.app')

@section('title', 'Backup & Restore Data')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Backup & Restore Data Sekolah</h1>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        <!-- Export / Backup -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Download Backup (Export)</h6>
                </div>
                <div class="card-body">
                    <p>Unduh seluruh data sekolah dalam format JSON.</p>

                    <form action="{{ route('backup.download') }}" method="GET">
                        @if(auth()->user()->isSuperAdmin())
                            <div class="form-group">
                                <label>Pilih Sekolah</label>
                                <select name="school_id" class="form-control" required>
                                    <option value="">-- Pilih Sekolah --</option>
                                    @foreach($schools as $school)
                                        <option value="{{ $school->id }}">{{ $school->name }} ({{ $school->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <i class="fas fa-school"></i> Sekolah: <strong>{{ auth()->user()->school->name ?? '-' }}</strong>
                            </div>
                        @endif

                        <button type="submit" class="btn btn-primary btn-icon-split btn-lg">
                            <span class="icon text-white-50">
                                <i class="fas fa-download"></i>
                            </span>
                            <span class="text">Download Backup</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Import / Restore -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4 border-left-danger">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-danger">Restore Data (Import)</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        <strong>PERHATIAN!</strong> Fitur ini akan <u>MENGHAPUS</u> seluruh data sekolah yang dipilih dan
                        menggantinya dengan data dari file backup.
                    </div>

                    <form action="{{ route('backup.restore') }}" method="POST" enctype="multipart/form-data"
                        onsubmit="return confirmRestore()">
                        @csrf
                        
                        @if(auth()->user()->isSuperAdmin())
                             <div class="form-group">
                                <label>Pilih Sekolah Target (Data akan ditimpa)</label>
                                <select name="school_id" class="form-control" required>
                                    <option value="">-- Pilih Sekolah --</option>
                                    @foreach($schools as $school)
                                        <option value="{{ $school->id }}">{{ $school->name }} ({{ $school->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="form-group">
                            <label>Pilih File Backup (.json)</label>
                            <input type="file" name="backup_file" class="form-control-file" accept=".json,.txt" required>
                        </div>

                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="confirmCheck" name="confirmation" required>
                            <label class="form-check-label" for="confirmCheck">Saya mengerti bahwa data sekolah yang dipilih
                                akan dihapus permanen.</label>
                        </div>

                        <button type="submit" class="btn btn-danger btn-icon-split">
                            <span class="icon text-white-50">
                                <i class="fas fa-upload"></i>
                            </span>
                            <span class="text">Restore Database</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmRestore() {
            return confirm("APAKAH ANDA YAKIN?\n\nSemua data sekolah saat ini akan DIHAPUS dan digantikan dengan data backup.\nTindakan ini tidak dapat dibatalkan.");
        }
    </script>
@endsection