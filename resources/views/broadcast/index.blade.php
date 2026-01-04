@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h1 class="h3 mb-4 text-gray-800">📢 Broadcast WhatsApp</h1>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Kirim Pesan ke Siswa</h6>
                    </div>
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        @if(session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <form action="{{ route('broadcast.send') }}" method="POST"
                            onsubmit="return confirm('Apakah Anda yakin ingin mengirim pesan ini ke banyak nomor?');">
                            @csrf

                            <div class="form-group">
                                <label for="target_class_id">Target Penerima</label>
                                <select name="target_class_id" id="target_class_id" class="form-control" required>
                                    <option value="" disabled selected>-- Pilih Target --</option>
                                    <option value="all" class="font-weight-bold">📢 SEMUA KELAS (All Students)</option>
                                    <optgroup label="Per Kelas">
                                        @foreach($kelas as $k)
                                            <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                                        @endforeach
                                    </optgroup>
                                </select>
                                <small class="text-muted">Pesan akan dikirim ke nomor WhatsApp yang terdaftar pada data
                                    siswa.</small>
                            </div>

                            <div class="form-group">
                                <label for="message">Isi Pesan</label>
                                <textarea name="message" id="message" rows="6" class="form-control"
                                    placeholder="Tulis pengumuman di sini..." required></textarea>
                                <small class="text-muted">
                                    * Pesan akan otomatis diawali dengan "📢 PENGUMUMAN SEKOLAH" dan sapaan nama siswa.<br>
                                    * Pesan akan otomatis diakhiri dengan signature admin.
                                </small>
                            </div>

                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary btn-lg btn-block">
                                    <i class="fas fa-paper-plane mr-2"></i> Kirim Broadcast
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection