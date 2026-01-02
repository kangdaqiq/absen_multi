@extends('layouts.app')

@section('title', 'Log WhatsApp')

@section('content')
    <h1 class="h3 mb-4 text-gray-800">Log Pengiriman WhatsApp</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Riwayat Pesan</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="15%">No Tujuan</th>
                            <th width="45%">Pesan</th>
                            <th width="10%">Status</th>
                            <th width="25%">Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->id }}</td>
                            <td>{{ $log->phone_number }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($log->message, 100) }}</td>
                            <td>
                                @if($log->status == 'sent')
                                    <span class="badge badge-success">Terkirim</span>
                                @elseif($log->status == 'pending')
                                    <span class="badge badge-warning">Pending</span>
                                @elseif($log->status == 'failed')
                                    <span class="badge badge-danger">Gagal</span>
                                @else
                                    <span class="badge badge-secondary">{{ $log->status }}</span>
                                @endif
                            </td>
                            <td>{{ $log->created_at }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">Belum ada log pesan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
@endsection
