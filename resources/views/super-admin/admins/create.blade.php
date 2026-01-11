@extends('layouts.app')

@section('title', 'Tambah Admin - ' . $school->name)

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-user-plus"></i> Tambah Admin Baru
            </h1>
            <p class="text-muted mb-0">Sekolah: <strong>{{ $school->name }}</strong></p>
        </div>
        <a href="{{ route('super-admin.schools.admins.index', $school) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <form action="{{ route('super-admin.schools.admins.store', $school) }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label for="full_name">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('full_name') is-invalid @enderror" id="full_name"
                                name="full_name" value="{{ old('full_name') }}" required>
                            @error('full_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="username">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('username') is-invalid @enderror" id="username"
                                name="username" value="{{ old('username') }}" required>
                            <small class="form-text text-muted">Username untuk login</small>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                                name="email" value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                id="password" name="password" required>
                            <small class="form-text text-muted">Minimal 8 karakter</small>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation">Konfirmasi Password <span
                                    class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password_confirmation"
                                name="password_confirmation" required>
                        </div>

                        <hr>

                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan
                            </button>
                            <a href="{{ route('super-admin.schools.admins.index', $school) }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informasi</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2"><i class="fas fa-info-circle text-info"></i> <strong>Akses Admin</strong></p>
                    <p class="text-muted small mb-3">Admin yang dibuat akan memiliki akses penuh ke data sekolah
                        <strong>{{ $school->name }}</strong> saja.</p>

                    <p class="mb-2"><i class="fas fa-lock text-info"></i> <strong>Password</strong></p>
                    <p class="text-muted small mb-3">Pastikan password aman dan minimal 8 karakter. Admin dapat mengubah
                        password setelah login.</p>

                    <p class="mb-2"><i class="fas fa-user-shield text-info"></i> <strong>Role</strong></p>
                    <p class="text-muted small mb-0">User ini akan dibuat dengan role <span
                            class="badge badge-info">Admin</span> dan terikat ke sekolah ini.</p>
                </div>
            </div>
        </div>
    </div>
@endsection