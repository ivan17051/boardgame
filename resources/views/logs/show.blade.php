@extends('layouts.layout')

@section('content')
@php
  $fmt = function ($value) {
    if ($value === null || $value === '') {
      return '—';
    }
    if (is_bool($value)) {
      return $value ? 'true' : 'false';
    }
    if (is_array($value) || is_object($value)) {
      return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
    return (string) $value;
  };
@endphp

<div class="app-content-header">
  <div class="container-fluid">
    <div class="row">
      <div class="col-sm-6">
        <h3 class="mb-0">Detail log #{{ $log->id }}</h3>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
          <li class="breadcrumb-item"><a href="{{ route('logs.index') }}">Log perubahan</a></li>
          <li class="breadcrumb-item active" aria-current="page">#{{ $log->id }}</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="app-content">
  <div class="container-fluid">
    <div class="mb-3">
      <a href="{{ route('logs.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Kembali
      </a>
    </div>

    <div class="card mb-3">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <div class="text-secondary small">Waktu</div>
            <div class="fw-semibold">{{ $log->created_at ? $log->created_at->format('d/m/Y H:i:s') : '—' }}</div>
          </div>
          <div class="col-md-2">
            <div class="text-secondary small">Tabel</div>
            <div class="fw-semibold"><code>{{ $log->table_name }}</code></div>
          </div>
          <div class="col-md-2">
            <div class="text-secondary small">ID data</div>
            <div class="fw-semibold">#{{ $log->record_id }}</div>
          </div>
          <div class="col-md-2">
            <div class="text-secondary small">Aksi</div>
            <div>
              @if ($log->action === 'update')
                <span class="badge text-bg-warning">update</span>
              @elseif ($log->action === 'delete')
                <span class="badge text-bg-danger">delete</span>
              @else
                <span class="badge text-bg-secondary">{{ $log->action }}</span>
              @endif
            </div>
          </div>
          <div class="col-md-3">
            <div class="text-secondary small">Diubah oleh</div>
            <div class="fw-semibold">{{ $log->user_name ?: '—' }}</div>
            @if ($log->user_id)
              <div class="small text-secondary">ID user {{ $log->user_id }}</div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header">
        <h3 class="card-title mb-0">Field yang berubah</h3>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 22%">Field</th>
                <th>Sebelum</th>
                <th>Sesudah</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($changed as $field => $diff)
                <tr>
                  <td><code>{{ $field }}</code></td>
                  <td class="font-monospace text-danger">{{ $fmt($diff['from']) }}</td>
                  <td class="font-monospace text-success">{{ $fmt($diff['to']) }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="3" class="text-center text-secondary py-4">
                    @if ($log->action === 'delete')
                      Data dihapus (lihat snapshot asli di bawah).
                    @else
                      Tidak ada perbedaan field.
                    @endif
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header">
            <h3 class="card-title mb-0">Data asli</h3>
          </div>
          <div class="card-body p-0">
            <pre class="mb-0 p-3 small" style="max-height: 420px; overflow: auto;">{{ json_encode($original, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header">
            <h3 class="card-title mb-0">Data setelah edit</h3>
          </div>
          <div class="card-body p-0">
            <pre class="mb-0 p-3 small" style="max-height: 420px; overflow: auto;">{{ $log->action === 'delete' ? 'null (dihapus)' : json_encode($new, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
