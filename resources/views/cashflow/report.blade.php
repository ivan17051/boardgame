@extends('layouts.layout')

@section('content')
@php
  $fmtRp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
  $printUrl = route('cashflow.report', [
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
    'print' => 1,
  ]);
@endphp

<div class="app-content-header">
  <div class="container-fluid">
    <div class="row">
      <div class="col-sm-6">
        <h3 class="mb-0">Laporan arus kas</h3>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
          <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
          <li class="breadcrumb-item"><a href="{{ route('cashflow.index') }}">Arus kas</a></li>
          <li class="breadcrumb-item active" aria-current="page">Laporan</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="app-content">
  <div class="container-fluid">
    <div class="card mb-4">
      <div class="card-header">
        <h3 class="card-title mb-0">Filter laporan</h3>
      </div>
      <div class="card-body">
        <form method="GET" action="{{ route('cashflow.report') }}" class="row g-3 align-items-end">
          <div class="col-md-4">
            <label for="date_from" class="form-label">Dari tanggal</label>
            <input type="date" class="form-control" id="date_from" name="date_from" value="{{ $dateFrom }}" required />
          </div>
          <div class="col-md-4">
            <label for="date_to" class="form-label">Sampai tanggal</label>
            <input type="date" class="form-control" id="date_to" name="date_to" value="{{ $dateTo }}" required />
          </div>
          <div class="col-md-4 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary">Tampilkan</button>
            <a href="{{ $printUrl }}" class="btn btn-outline-secondary" target="_blank" rel="noopener noreferrer" data-no-page-loader>
              <i class="bi bi-printer me-1"></i> Cetak
            </a>
          </div>
        </form>
      </div>
    </div>

    <div class="row mb-3">
      <div class="col-md-3 col-6">
        <div class="small-box text-bg-success">
          <div class="inner">
            <h3 class="fs-5">{{ $fmtRp($summary['total_income_bayar']) }}</h3>
            <p class="mb-0">Pemasukan ({{ $summary['count_income'] }} transaksi)</p>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-6">
        <div class="small-box text-bg-light border">
          <div class="inner text-dark">
            <h3 class="fs-5">{{ $fmtRp($summary['total_income_tagihan']) }}</h3>
            <p class="mb-0">Total tagihan</p>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-6">
        <div class="small-box text-bg-primary">
          <div class="inner">
            <h3 class="fs-5">{{ $fmtRp($summary['total_sewa_meja']) }}</h3>
            <p class="mb-0">Sewa Meja</p>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-6">
        <div class="small-box text-bg-info">
          <div class="inner">
            <h3 class="fs-5">{{ $fmtRp($summary['total_additional_fb']) }}</h3>
            <p class="mb-0">Additional Item (F&amp;B)</p>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Pratinjau — {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</h3>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Tanggal</th>
                <th>Kategori</th>
                <th>Keterangan</th>
                <th>Metode</th>
                <th class="text-end">Tagihan</th>
                <th class="text-end">Jumlah bayar</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($incomeRows as $row)
                @php $st = $row->kelengkapanStatus(); @endphp
                <tr>
                  <td class="text-nowrap small">{{ $row->waktu_pembayaran->format('d/m/Y H:i') }}</td>
                  <td class="small">{{ \App\Models\CashFlow::kategoriPendapatanLabel($row->kategori_pendapatan) }}</td>
                  <td class="text-break small">{{ $row->keterangan ?: '—' }}</td>
                  <td class="small">{{ \App\Models\CashFlow::metodePembayaranLabel($row->paymentMetode()) }}</td>
                  <td class="text-end font-monospace small">{{ $fmtRp($row->total) }}</td>
                  <td class="text-end font-monospace small text-success">{{ $fmtRp($row->amountPaid()) }}</td>
                  <td>
                    @if ($st === 'lengkap')
                      <span class="badge text-bg-success">Lengkap</span>
                    @elseif ($st === 'sebagian')
                      <span class="badge text-bg-info text-dark">Sebagian</span>
                    @else
                      <span class="badge text-bg-warning text-dark">Belum</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-secondary py-4">Tidak ada pemasukan pada periode ini.</td>
                </tr>
              @endforelse
            </tbody>
            @if ($incomeRows->isNotEmpty())
              <tfoot class="table-light">
                <tr>
                  <th colspan="4" class="text-end">Total</th>
                  <th class="text-end font-monospace">{{ $fmtRp($summary['total_income_tagihan']) }}</th>
                  <th class="text-end font-monospace text-success">{{ $fmtRp($summary['total_income_bayar']) }}</th>
                  <th></th>
                </tr>
              </tfoot>
            @endif
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
