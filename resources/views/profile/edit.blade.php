@extends('layouts.app')

@section('title', 'Edit Profil')

@section('content')
    <h1 class="h3 mb-4 text-gray-800">Edit Profil</h1>

    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informasi Akun</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $user->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>
                        
                        <!-- Global Logo (Super Admin Only) -->
                        @if(auth()->user()->isSuperAdmin())
                            <h6 class="font-weight-bold text-primary mb-3">Logo Global (Super Admin)</h6>
                            <div class="form-group">
                                <label>Upload Logo Baru</label>
                                <div class="mb-2">
                                     @if(isset($school_logo))
                                        @php
                                            $logo = $school_logo;
                                            $isStorage = \Illuminate\Support\Str::startsWith($logo, 'schools/');
                                            $logoUrl = $isStorage ? asset('storage/' . $logo) : asset('img/' . $logo);
                                        @endphp
                                        <img src="{{ $logoUrl }}" alt="Current Logo" style="max-height: 100px; border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                                     @endif
                                </div>
                                <input type="file" name="global_logo" class="form-control-file" accept="image/*">
                                <small class="text-muted">Logo ini akan tampil di Halaman Login dan Dashboard Super Admin (Global).</small>
                                @error('global_logo')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <hr>
                        @endif

                        <h6 class="font-weight-bold text-primary mb-3">Ganti Password (Opsional)</h6>

                        <div class="form-group">
                            <label>Password Saat Ini</label>
                            <input type="password" name="current_password"
                                class="form-control @error('current_password') is-invalid @enderror">
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Password Baru</label>
                            <input type="password" name="new_password"
                                class="form-control @error('new_password') is-invalid @enderror">
                            <small class="text-muted">Minimal 8 karakter. Kosongkan jika tidak ingin mengganti
                                password.</small>
                            @error('new_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Konfirmasi Password Baru</label>
                            <input type="password" name="new_password_confirmation" class="form-control">
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection