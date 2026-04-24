@extends('layouts.app')

@section('title', 'Edit Sekolah')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-school"></i> Edit Sekolah: {{ $school->name }}
        </h1>
        <a href="{{ route('super-admin.schools.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <form action="{{ route('super-admin.schools.update', $school) }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="name">Nama Sekolah <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                name="name" value="{{ old('name', $school->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="code">Kode Sekolah</label>
                            <input type="text" class="form-control" id="code"
                                value="{{ $school->code }}" readonly disabled>
                            <small class="form-text text-muted">Kode unik sekolah tidak dapat diubah.</small>
                        </div>

                        <div class="form-group">
                            <label for="address">Alamat</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" id="address"
                                name="address" rows="3">{{ old('address', $school->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">Telepon</label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone"
                                        name="phone" value="{{ old('phone', $school->phone) }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                                        name="email" value="{{ old('email', $school->email) }}">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="operator_phone">
                                <i class="fas fa-headset text-primary mr-1"></i>
                                Nomor Operator / PIC Sekolah
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                </div>
                                <input type="text" class="form-control @error('operator_phone') is-invalid @enderror"
                                    id="operator_phone" name="operator_phone"
                                    value="{{ old('operator_phone', $school->operator_phone) }}"
                                    placeholder="Contoh: 08123456789">
                            </div>
                            <small class="form-text text-muted">
                                Nomor WhatsApp operator/PIC yang dapat dihubungi untuk koordinasi teknis sekolah ini.
                            </small>
                            @error('operator_phone')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="logo">Logo Sekolah</label>
                            @if($school->logo)
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $school->logo) }}" alt="Logo" class="img-thumbnail"
                                        style="max-height: 100px;">
                                    <p class="text-muted small mb-0">Logo saat ini</p>
                                </div>
                            @endif
                            <input type="file" class="form-control-file @error('logo') is-invalid @enderror" id="logo"
                                name="logo" accept="image/*">
                            <small class="form-text text-muted">Kosongkan jika tidak ingin mengubah logo. Format: JPG, PNG.
                                Maksimal 10MB</small>
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
                                id="student_limit" name="student_limit"
                                value="{{ old('student_limit', $school->student_limit) }}" min="0">
                            <small class="form-text text-muted">Biarkan kosong atau 0 untuk Unlimited (Tidak
                                Terbatas)</small>
                            @error('student_limit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
                                    value="1" {{ old('is_active', $school->is_active) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active">Aktifkan Sekolah</label>
                            </div>

                            <div class="custom-control custom-checkbox mt-2">
                                <input type="checkbox" class="custom-control-input" id="wa_enabled" name="wa_enabled"
                                    value="1" {{ old('wa_enabled', $school->wa_enabled) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="wa_enabled">Aktifkan Notifikasi WhatsApp</label>
                            </div>
                        </div>

                        <hr>

                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update
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
                    <h6 class="m-0 font-weight-bold text-primary">Statistik Sekolah</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-muted">Total Siswa</h6>
                        <h3 class="mb-0">{{ $school->siswa()->count() }}</h3>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-muted">Total Guru</h6>
                        <h3 class="mb-0">{{ $school->guru()->count() }}</h3>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-muted">Total Admin</h6>
                        <h3 class="mb-0">{{ $school->users()->where('role', 'admin')->count() }}</h3>
                    </div>
                    <hr>
                    <a href="{{ route('super-admin.schools.admins.index', $school) }}" class="btn btn-info btn-block">
                        <i class="fas fa-users-cog"></i> Kelola Admin
                    </a>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informasi</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2"><small class="text-muted">Dibuat:</small></p>
                    <p class="mb-3">{{ $school->created_at->format('d M Y H:i') }}</p>

                    <p class="mb-2"><small class="text-muted">Terakhir diupdate:</small></p>
                    <p class="mb-0">{{ $school->updated_at->format('d M Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection