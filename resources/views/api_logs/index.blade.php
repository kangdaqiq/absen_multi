@extends('layouts.app')

@section('title', 'Log API')

@section('content')
    <h1 class="h3 mb-4 text-gray-800">Log Penggunaan API System</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Riwayat Request API</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="15%">Waktu</th>
                            <th width="10%">Action</th>
                            <th width="10%">UID</th>
                            <th width="10%">Status</th>
                            <th width="35%">Pesan</th>
                            <th width="10%">API Key</th>
                            <th width="10%">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->created_at }}</td>
                            <td>{{ $log->action }}</td>
                            <td><small>{{ $log->uid }}</small></td>
                            <td>
                                @if($log->success)
                                    <span class="badge badge-success">Sukses</span>
                                @else
                                    <span class="badge badge-danger">Gagal</span>
                                @endif
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($log->message, 80) }}</td>
                            <td><small>{{ substr($log->api_key, 0, 8) }}...</small></td>
                            <td>{{ $log->ip_address }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">Belum ada log API.</td>
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
