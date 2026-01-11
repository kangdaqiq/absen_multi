@extends('layouts.app')

@section('title', 'Tambah Sekolah')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-school"></i> Tambah Sekolah Baru
        </h1>
        <a href="{{ route('super-admin.schools.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <form action="{{ route('super-admin.schools.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group">
                            <label for="name">Nama Sekolah <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="code">Kode Sekolah <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code"
                                name="code" value="{{ old('code') }}" required>
                            <small class="form-text text-muted">Kode unik untuk sekolah (contoh: SDN01, SMPN02)</small>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="address">Alamat</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" id="address"
                                name="address" rows="3">{{ old('address') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">Telepon</label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone"
                                        name="phone" value="{{ old('phone') }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                                        name="email" value="{{ old('email') }}">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="logo">Logo Sekolah</label>
                            <input type="file" class="form-control-file @error('logo') is-invalid @enderror" id="logo"
                                name="logo" accept="image/*">
                            <small class="form-text text-muted">Format: JPG, PNG. Maksimal 10MB</small>
                            @error('logo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @error('logo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="student_limit">Limit / Jumlah Siswa (Quota)</label>
                            <input type="number" class="form-control @error('student_limit') is-invalid @enderror"
                                id="student_limit" name="student_limit" value="{{ old('student_limit') }}" min="0">
                            <small class="form-text text-muted">Biarkan kosong atau 0 untuk Unlimited (Tidak
                                Terbatas)</small>
                            @error('student_limit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
                                    value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active">Aktifkan Sekolah</label>
                            </div>

                            <div class="custom-control custom-checkbox mt-2">
                                <input type="checkbox" class="custom-control-input" id="wa_enabled" name="wa_enabled"
                                    value="1" {{ old('wa_enabled', true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="wa_enabled">Aktifkan Notifikasi WhatsApp</label>
                            </div>
                        </div>

                        <hr>

                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan
                            </button>
                            <a href="{{ route('super-admin.schools.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informasi</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2"><i class="fas fa-info-circle text-info"></i> <strong>Kode Sekolah</strong></p>
                    <p class="text-muted small mb-3">Kode unik yang akan digunakan sebagai identifier sekolah. Pastikan kode
                        mudah diingat dan tidak sama dengan sekolah lain.</p>

                    <p class="mb-2"><i class="fas fa-image text-info"></i> <strong>Logo</strong></p>
                    <p class="text-muted small mb-3">Logo akan ditampilkan di dashboard dan laporan sekolah. Gunakan gambar
                        dengan resolusi minimal 200x200px.</p>

                    <p class="mb-2"><i class="fas fa-check-circle text-info"></i> <strong>Status Aktif</strong></p>
                    <p class="text-muted small mb-0">Hanya sekolah yang aktif yang dapat digunakan untuk absensi dan fitur
                        lainnya.</p>
                </div>
            </div>
        </div>
    </div>
@endsection