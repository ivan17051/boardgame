<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Laporan arus kas {{ \Carbon\Carbon::parse($dateFrom)->format('d-m-Y') }} — {{ config('app.name', 'Omahjong') }}</title>
  <style>
    * { box-sizing: border-box; }
    body {
      font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      margin: 0;
      padding: 1.25rem;
      color: #1a1a1a;
      background: #fff;
      font-size: 11pt;
      line-height: 1.4;
    }
    .wrap { max-width: 900px; margin: 0 auto; }
    .header {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding-bottom: 0.75rem;
      border-bottom: 2px solid #006131;
      margin-bottom: 1rem;
    }
    .logo { width: 64px; height: 64px; object-fit: contain; }
    .brand h1 { margin: 0; font-size: 1.2rem; color: #006131; }
    .brand p { margin: 0.15rem 0 0; font-size: 0.85rem; color: #555; }
    .doc-title {
      text-align: center;
      font-size: 1rem;
      font-weight: 700;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      margin: 0.75rem 0 1rem;
    }
    .meta-row {
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 0.5rem 1.5rem;
      font-size: 0.9rem;
      margin-bottom: 1rem;
      color: #444;
    }
    .summary-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 0.5rem;
      margin-bottom: 1.25rem;
    }
    .summary-box {
      border: 1px solid #ccc;
      border-radius: 6px;
      padding: 0.5rem 0.65rem;
      background: #f8f9fa;
    }
    .summary-box .lbl { font-size: 0.75rem; color: #555; margin-bottom: 0.2rem; }
    .summary-box .val { font-weight: 700; font-size: 0.95rem; }
    table.data {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.85rem;
    }
    table.data th,
    table.data td {
      border: 1px solid #bbb;
      padding: 0.35rem 0.45rem;
      vertical-align: top;
    }
    table.data th {
      background: #e9ecef;
      font-weight: 600;
      text-align: left;
    }
    table.data tfoot th,
    table.data tfoot td {
      background: #f1f3f5;
      font-weight: 700;
    }
    .text-end { text-align: right; }
    .text-success { color: #0d6832; }
    .footer {
      margin-top: 1.5rem;
      padding-top: 0.75rem;
      border-top: 1px solid #ccc;
      font-size: 0.75rem;
      color: #666;
      text-align: center;
    }
    .metode-list { font-size: 0.85rem; margin: 0 0 1rem; padding-left: 1.2rem; }
    .actions { margin-top: 1rem; text-align: center; }
    .btn-print {
      padding: 0.45rem 1rem;
      background: #006131;
      color: #fff;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 0.9rem;
    }
    @media print {
      body { padding: 0; }
      .no-print { display: none !important; }
      .wrap { max-width: none; }
      table.data { page-break-inside: auto; }
      tr { page-break-inside: avoid; }
    }
    @page { margin: 12mm; }
    @media (max-width: 700px) {
      .summary-grid { grid-template-columns: repeat(2, 1fr); }
    }
  </style>
</head>
<body>
@php
  $fmtRp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
  $periodLabel = $dateFrom === $dateTo
    ? \Carbon\Carbon::parse($dateFrom)->format('d F Y')
    : \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') . ' – ' . \Carbon\Carbon::parse($dateTo)->format('d/m/Y');
@endphp
  <div class="wrap">
    <header class="header">
      <img class="logo" src="{{ asset('public/assets/img/logo.png') }}" alt="Logo" width="64" height="64" />
      <div class="brand">
        <h1>{{ config('app.name', 'Omahjong') }}</h1>
        <p>Laporan arus kas / pemasukan sewa meja</p>
      </div>
    </header>

    <p class="doc-title">Laporan arus kas</p>

    <div class="meta-row">
      <span><strong>Periode:</strong> {{ $periodLabel }}</span>
      <span><strong>Dicetak:</strong> {{ now()->format('d/m/Y H:i') }}</span>
    </div>

    <div class="summary-grid">
      <div class="summary-box">
        <div class="lbl">Total pemasukan</div>
        <div class="val text-success">{{ $fmtRp($summary['total_income_bayar']) }}</div>
      </div>
      <div class="summary-box">
        <div class="lbl">Sewa Meja</div>
        <div class="val">{{ $fmtRp($summary['total_sewa_meja']) }}</div>
      </div>
      <div class="summary-box">
        <div class="lbl">Additional (F&amp;B)</div>
        <div class="val">{{ $fmtRp($summary['total_additional_fb']) }}</div>
      </div>
      <div class="summary-box">
        <div class="lbl">Jumlah baris</div>
        <div class="val">{{ $summary['count_income'] }}</div>
      </div>
    </div>

    @if ($summary['by_metode']->isNotEmpty())
      <p style="margin:0 0 0.35rem;font-weight:600;font-size:0.9rem;">Ringkasan metode pembayaran</p>
      <ul class="metode-list">
        @foreach ($summary['by_metode'] as $metode => $info)
          <li>{{ \App\Models\CashFlow::metodePembayaranLabel($metode) }}: {{ $info['count'] }} transaksi — {{ $fmtRp($info['total']) }}</li>
        @endforeach
      </ul>
    @endif

    <table class="data">
      <thead>
        <tr>
          <th style="width:32px">No</th>
          <th style="width:110px">Tanggal</th>
          <th style="width:100px">Kategori</th>
          <th>Keterangan</th>
          <th style="width:90px">Metode</th>
          <th class="text-end" style="width:95px">Tagihan</th>
          <th class="text-end" style="width:95px">Dibayar</th>
          <th style="width:72px">Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($incomeRows as $i => $row)
          @php $st = $row->kelengkapanStatus(); @endphp
          <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $row->waktu_pembayaran->format('d/m/Y H:i') }}</td>
            <td>{{ \App\Models\CashFlow::kategoriPendapatanLabel($row->kategori_pendapatan) }}</td>
            <td>{{ $row->keterangan ?: '—' }}</td>
            <td>{{ \App\Models\CashFlow::metodePembayaranLabel($row->metode_pembayaran) }}</td>
            <td class="text-end">{{ number_format((float) $row->total, 0, ',', '.') }}</td>
            <td class="text-end">{{ number_format($row->amountPaid(), 0, ',', '.') }}</td>
            <td>{{ $row->kelengkapanStatusLabel() }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="7" style="text-align:center;color:#666;">Tidak ada data pada periode ini.</td>
          </tr>
        @endforelse
      </tbody>
      @if ($incomeRows->isNotEmpty())
        <tfoot>
          <tr>
            <th colspan="4" class="text-end">Total</th>
            <th class="text-end">{{ number_format($summary['total_income_tagihan'], 0, ',', '.') }}</th>
            <th class="text-end">{{ number_format($summary['total_income_bayar'], 0, ',', '.') }}</th>
            <th></th>
          </tr>
        </tfoot>
      @endif
    </table>

    @if ($expenseRows->isNotEmpty())
      <p style="margin:1.25rem 0 0.5rem;font-weight:600;font-size:0.9rem;">Pengeluaran</p>
      <table class="data">
        <thead>
          <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Keterangan</th>
            <th class="text-end">Jumlah</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($expenseRows as $i => $row)
            <tr>
              <td>{{ $i + 1 }}</td>
              <td>{{ $row->waktu_pembayaran->format('d/m/Y H:i') }}</td>
              <td>{{ $row->keterangan ?: '—' }}</td>
              <td class="text-end">{{ number_format((float) $row->total, 0, ',', '.') }}</td>
            </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr>
            <th colspan="3" class="text-end">Total pengeluaran</th>
            <th class="text-end">{{ number_format($summary['total_expense'], 0, ',', '.') }}</th>
          </tr>
        </tfoot>
      </table>
    @endif

    <div class="footer">
      Dokumen ini dicetak dari sistem {{ config('app.name', 'Omahjong') }} — laporan arus kas periode {{ $periodLabel }}.
    </div>

    <div class="actions no-print">
      <button type="button" class="btn-print" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>
  </div>
</body>
</html>
