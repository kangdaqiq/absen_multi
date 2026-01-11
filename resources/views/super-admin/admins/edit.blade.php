@extends('layouts.app')

@section('title', 'Edit Admin - ' . $school->name)

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-user-edit"></i> Edit Admin: {{ $admin->full_name }}
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
                    <form action="{{ route('super-admin.schools.admins.update', [$school, $admin]) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="full_name">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('full_name') is-invalid @enderror" id="full_name"
                                name="full_name" value="{{ old('full_name', $admin->full_name) }}" required>
                            @error('full_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="username">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('username') is-invalid @enderror" id="username"
                                name="username" value="{{ old('username', $admin->username) }}" required>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                                name="email" value="{{ old('email', $admin->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>
                        <h6 class="font-weight-bold">Ubah Password (Opsional)</h6>
                        <p class="text-muted small">Kosongkan jika tidak ingin mengubah password</p>

                        <div class="form-group">
                            <label for="password">Password Baru</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                id="password" name="password">
                            <small class="form-text text-muted">Minimal 8 karakter</small>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" id="password_confirmation"
                                name="password_confirmation">
                        </div>

                        <hr>

                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update
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
                    <h6 class="m-0 font-weight-bold text-primary">Informasi Admin</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2"><small class="text-muted">Sekolah:</small></p>
                    <p class="mb-3"><strong>{{ $school->name }}</strong></p>

                    <p class="mb-2"><small class="text-muted">Role:</small></p>
                    <p class="mb-3"><span class="badge badge-info">{{ ucfirst($admin->role) }}</span></p>

                    <p class="mb-2"><small class="text-muted">Dibuat:</small></p>
                    <p class="mb-3">{{ $admin->created_at->format('d M Y H:i') }}</p>

                    <p class="mb-2"><small class="text-muted">Terakhir diupdate:</small></p>
                    <p class="mb-0">{{ $admin->updated_at->format('d M Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection