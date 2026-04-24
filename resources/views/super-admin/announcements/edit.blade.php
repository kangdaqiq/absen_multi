@extends('layouts.app')

@section('title', 'Edit Pengumuman')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-edit text-primary mr-2"></i> Edit Pengumuman
    </h1>
    <a href="{{ route('super-admin.announcements.index') }}" class="btn btn-sm btn-secondary shadow-sm">
        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-body">
                <form action="{{ route('super-admin.announcements.update', $announcement) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="form-group">
                        <label for="title">Judul Pengumuman <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $announcement->title) }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="content">Isi Pengumuman <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('content') is-invalid @enderror" id="content" name="content" rows="6" required>{{ old('content', $announcement->content) }}</textarea>
                        <small class="form-text text-muted">Isi pengumuman ini akan ditampilkan di dashboard Admin Sekolah.</small>
                        @error('content')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', $announcement->is_active) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active">Aktif (Tampilkan di Dashboard)</label>
                        </div>
                    </div>

                    <hr>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Perbarui
                    </button>
                    <a href="{{ route('super-admin.announcements.index') }}" class="btn btn-secondary">Batal</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
