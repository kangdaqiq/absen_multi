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
                            onsubmit="return confirm('Apakah Anda yakin ingin mengirim pesan ini?');">
                            @csrf

                            <div class="form-group">
                                <label class="font-weight-bold h5 mb-3">Target Penerima</label>

                                <div class="custom-control custom-checkbox mb-3 p-3 bg-light rounded border">
                                    <input type="checkbox" class="custom-control-input" id="checkAll">
                                    <label class="custom-control-label font-weight-bold text-primary" for="checkAll">📢
                                        PILIH SEMUA KELAS</label>
                                </div>

                                <div class="row ml-1">
                                    @foreach($kelas as $k)
                                        <div class="col-md-3 col-sm-4 mb-2">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input class-checkbox"
                                                    name="target_class_ids[]" value="{{ $k->id }}" id="kelas_{{ $k->id }}">
                                                <label class="custom-control-label" for="kelas_{{ $k->id }}">
                                                    {{ $k->nama_kelas }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <small class="text-muted mt-2 d-block">* Pilih minimal satu kelas untuk dikirim
                                    pesan.</small>
                            </div>

                            <hr>

                            <div class="form-group">
                                <label for="message" class="font-weight-bold h5">Isi Pesan</label>
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

@section('scripts')
    <script>
        document.getElementById('checkAll').addEventListener('change', function () {
            var checkboxes = document.querySelectorAll('.class-checkbox');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = this.checked;
            }
        });

        var classCheckboxes = document.querySelectorAll('.class-checkbox');
        classCheckboxes.forEach(function (box) {
            box.addEventListener('change', function () {
                var allChecked = document.querySelectorAll('.class-checkbox:checked').length === classCheckboxes.length;
                document.getElementById('checkAll').checked = allChecked;
            });
        });
    </script>
@endsection