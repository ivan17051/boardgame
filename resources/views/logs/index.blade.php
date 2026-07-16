@extends('layouts.layout')

@section('content')
<div class="app-content-header">
  <div class="container-fluid">
    <div class="row">
      <div class="col-sm-6">
        <h3 class="mb-0">Log perubahan</h3>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
          <li class="breadcrumb-item"><a href="#">Data Master</a></li>
          <li class="breadcrumb-item active" aria-current="page">Log perubahan</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="app-content">
  <div class="container-fluid">
    <div class="card mb-3">
      <div class="card-body">
        <form method="get" action="{{ route('logs.index') }}" class="row g-2 align-items-end">
          <div class="col-md-4">
            <label for="logSearch" class="form-label">Cari</label>
            <input
              type="search"
              class="form-control"
              id="logSearch"
              name="q"
              value="{{ $search }}"
              placeholder="ID sewa, nama user, aksi..."
            />
          </div>
          <div class="col-md-2">
            <label for="dateFrom" class="form-label">Tanggal mulai</label>
            <input
              type="date"
              class="form-control"
              id="dateFrom"
              name="date_from"
              value="{{ $dateFrom }}"
            />
          </div>
          <div class="col-md-2">
            <label for="dateTo" class="form-label">Tanggal akhir</label>
            <input
              type="date"
              class="form-control"
              id="dateTo"
              name="date_to"
              value="{{ $dateTo }}"
            />
          </div>
          <div class="col-md-4">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-search me-1"></i>Filter
            </button>
            <a href="{{ route('logs.index') }}" class="btn btn-outline-secondary">Reset</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3 class="card-title mb-0">Riwayat edit data</h3>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Waktu</th>
                <th>ID data</th>
                <th>Aksi</th>
                <th>Diubah oleh</th>
                <th>Ringkasan perubahan</th>
                <th class="text-end" style="width: 110px">Detail</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($logs as $log)
                @php
                  $changed = $log->changedFields();
                  $changeKeys = array_keys($changed);
                  $preview = collect($changeKeys)->take(4)->implode(', ');
                  if (count($changeKeys) > 4) {
                    $preview .= ' +'.(count($changeKeys) - 4);
                  }
                @endphp
                <tr>
                  <td class="text-nowrap">
                    {{ $log->created_at ? $log->created_at->format('d/m/Y H:i:s') : '—' }}
                  </td>
                  <td>#{{ $log->record_id }}</td>
                  <td>
                    @if ($log->action === 'update')
                      <span class="badge text-bg-warning">update</span>
                    @elseif ($log->action === 'delete')
                      <span class="badge text-bg-danger">delete</span>
                    @else
                      <span class="badge text-bg-secondary">{{ $log->action }}</span>
                    @endif
                  </td>
                  <td>
                    {{ $log->user_name ?: '—' }}
                    @if ($log->user_id)
                      <div class="small text-secondary">ID user {{ $log->user_id }}</div>
                    @endif
                  </td>
                  <td class="small">
                    @if ($log->action === 'delete')
                      Data dihapus
                    @elseif ($preview !== '')
                      {{ $preview }}
                    @else
                      <span class="text-secondary">Tidak ada field berubah</span>
                    @endif
                  </td>
                  <td class="text-end">
                    <a href="{{ route('logs.show', $log) }}" class="btn btn-sm btn-outline-primary">
                      Lihat
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center text-secondary py-4">
                    Belum ada log perubahan.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      @if ($logs->hasPages())
        <div class="card-footer">
          {{ $logs->links() }}
        </div>
      @endif
    </div>
  </div>
</div>
@endsection
