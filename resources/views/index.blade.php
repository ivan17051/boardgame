@extends('layouts.layout')

@section('content')
@php
  $fmtRp = fn ($n) => number_format((float) $n, 0, ',', '.');
@endphp

<style>
  .dashboard-page .small-box .inner {
    overflow: hidden;
  }
  .dashboard-page .small-box .inner h3,
  .dashboard-page .small-box .inner p,
  .dashboard-page .small-box-footer {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .dashboard-page .info-box {
    min-width: 0;
  }
  .dashboard-page .info-box-content {
    min-width: 0;
    overflow: hidden;
  }
  .dashboard-page .info-box-text,
  .dashboard-page .info-box-number {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .dashboard-page .dashboard-ellipsis {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
</style>

<div class="app-content-header dashboard-page">
  <div class="container-fluid">
    <div class="row">
      <div class="col-sm-6">
        <h3 class="mb-0">Dashboard</h3>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
          <li class="breadcrumb-item active" aria-current="page">Beranda</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="app-content dashboard-page">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-3 col-6">
        <div class="small-box text-bg-warning">
          <div class="inner">
            <h3>{{ $stats['active_rentals'] }}</h3>
            <p>Sewa aktif</p>
          </div>
          <i class="small-box-icon bi bi-clock-history"></i>
          <a href="{{ route('rental.index') }}" class="small-box-footer link-dark link-underline-opacity-0 link-underline-opacity-50-hover">
            Lihat sewa <i class="bi bi-link-45deg"></i>
          </a>
        </div>
      </div>
      <div class="col-lg-3 col-6">
        <div class="small-box text-bg-success">
          <div class="inner">
            <h3>Rp {{ $fmtRp($stats['income_today']) }}</h3>
            <p>Pemasukan hari ini</p>
          </div>
          <i class="small-box-icon bi bi-cash-coin"></i>
          <a href="{{ route('cashflow.index') }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
            Arus kas <i class="bi bi-link-45deg"></i>
          </a>
        </div>
      </div>
      <div class="col-lg-3 col-6">
        <div class="small-box text-bg-primary">
          <div class="inner">
            <h3>Rp {{ $fmtRp($stats['income_month']) }}</h3>
            <p>Pemasukan bulan ini</p>
          </div>
          <i class="small-box-icon bi bi-graph-up-arrow"></i>
          <a href="{{ route('cashflow.index') }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
            Detail <i class="bi bi-link-45deg"></i>
          </a>
        </div>
      </div>
      <div class="col-lg-3 col-6">
        <div class="small-box text-bg-danger">
          <div class="inner">
            <h3>{{ $stats['pending_payment'] }}</h3>
            <p>Pembayaran belum lengkap</p>
          </div>
          <i class="small-box-icon bi bi-exclamation-circle"></i>
          <a href="{{ route('cashflow.index') }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
            Lengkapi <i class="bi bi-link-45deg"></i>
          </a>
        </div>
      </div>
    </div>

    <div class="row mb-4">
      <div class="col-md-4">
        <div class="info-box">
          <span class="info-box-icon text-bg-warning shadow-sm"><i class="bi bi-table"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Meja disewa</span>
            <span class="info-box-number">{{ $stats['meja_rented'] }} / {{ $stats['total_meja'] }}</span>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="info-box">
          <span class="info-box-icon text-bg-success shadow-sm"><i class="bi bi-check2-circle"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Meja tersedia</span>
            <span class="info-box-number">{{ $stats['meja_available'] }}</span>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="info-box">
          <span class="info-box-icon text-bg-info shadow-sm"><i class="bi bi-calendar-check"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Sewa selesai hari ini</span>
            <span class="info-box-number">{{ $stats['completed_today'] }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-8">
        <div class="card mb-4">
          <div class="card-header">
            <h3 class="card-title mb-0">Pemasukan 7 hari terakhir</h3>
          </div>
          <div class="card-body">
            <div id="dashboard-income-chart" style="min-height: 280px;"></div>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card mb-4">
          <div class="card-header">
            <div class="row">
              <div class="col-md-8">
                <h3 class="card-title mb-0">Arus kas terbaru</h3>
              </div>
              <div class="col-md-4 text-end">
                <a href="{{ route('cashflow.index') }}" class="btn btn-sm btn-outline-secondary">Semua</a>
              </div>
            </div>
          </div>
          <div class="card-body p-0">
            <ul class="list-group list-group-flush">
              @forelse ($recentCashflow as $row)
                @php $st = $row->kelengkapanStatus(); @endphp
                <li class="list-group-item">
                  <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="min-w-0 flex-grow-1">
                      <div class="fw-semibold text-success dashboard-ellipsis" title="+ Rp {{ $fmtRp($row->total) }}">
                        + Rp {{ $fmtRp($row->total) }}
                        @if ($st === 'lengkap')
                          <span class="badge text-bg-success">Lengkap</span>
                        @elseif ($st === 'sebagian')
                          <span class="badge text-bg-info text-dark">Sebagian</span>
                        @else
                          <span class="badge text-bg-warning text-dark">Belum</span>
                        @endif
                      </div>
                      <div class="small text-secondary dashboard-ellipsis" title="{{ $row->keterangan ?: '—' }}">{{ $row->keterangan ?: '—' }}</div>
                      <div class="small text-secondary dashboard-ellipsis">{{ $row->waktu_pembayaran->format('d/m/Y H:i') }}</div>
                    </div>
                  </div>
                </li>
              @empty
                <li class="list-group-item text-center text-secondary py-4">Belum ada pemasukan.</li>
              @endforelse
            </ul>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  const chartEl = document.querySelector('#dashboard-income-chart');
  if (!chartEl || typeof ApexCharts === 'undefined') return;

  const labels = @json($chartLabels);
  const values = @json($chartValues);

  const chart = new ApexCharts(chartEl, {
    series: [{ name: 'Pemasukan', data: values }],
    chart: {
      type: 'bar',
      height: 280,
      toolbar: { show: false },
      fontFamily: 'inherit',
    },
    colors: ['#006131'],
    plotOptions: {
      bar: { borderRadius: 4, columnWidth: '55%' },
    },
    dataLabels: { enabled: false },
    xaxis: { categories: labels },
    yaxis: {
      labels: {
        formatter: function (v) {
          return 'Rp ' + Math.round(v).toLocaleString('id-ID');
        },
      },
    },
    tooltip: {
      y: {
        formatter: function (v) {
          return 'Rp ' + Math.round(v).toLocaleString('id-ID');
        },
      },
    },
    grid: { strokeDashArray: 4 },
  });
  chart.render();
})();
</script>
@endpush
