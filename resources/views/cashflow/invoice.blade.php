<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Kuitansi #{{ str_pad((string) $cashFlow->id, 6, '0', STR_PAD_LEFT) }} — {{ config('app.name', 'Omahjong') }}</title>
  <style>
    * { box-sizing: border-box; }
    body {
      font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      margin: 0;
      padding: 1.5rem;
      color: #1a1a1a;
      background: #fff;
      line-height: 1.45;
    }
    .wrap {
      max-width: 640px;
      margin: 0 auto;
    }
    .header {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding-bottom: 1rem;
      border-bottom: 2px solid #006131;
      margin-bottom: 1.25rem;
    }
    .logo {
      width: 72px;
      height: 72px;
      object-fit: contain;
      flex-shrink: 0;
    }
    .brand-meta h1 {
      margin: 0 0 0.25rem;
      font-size: 1.25rem;
      font-weight: 700;
      color: #006131;
    }
    .brand-meta p {
      margin: 0;
      font-size: 0.85rem;
      color: #555;
    }
    .doc-title {
      text-align: center;
      font-size: 1.1rem;
      font-weight: 700;
      letter-spacing: 0.06em;
      margin: 1rem 0 1.25rem;
      text-transform: uppercase;
    }
    table.meta {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 1rem;
      font-size: 0.95rem;
    }
    table.meta th,
    table.meta td {
      padding: 0.45rem 0;
      vertical-align: top;
      text-align: left;
    }
    table.meta th {
      width: 42%;
      color: #555;
      font-weight: 600;
    }
    .amount-box {
      background: #f4fbf7;
      border: 1px solid #c5e4d4;
      border-radius: 8px;
      padding: 1rem 1.25rem;
      margin: 1.25rem 0;
    }
    .amount-box .label {
      font-size: 0.8rem;
      color: #333;
      margin-bottom: 0.35rem;
    }
    .amount-box .value {
      font-size: 1.35rem;
      font-weight: 700;
      color: #006131;
    }
    .footer {
      margin-top: 2rem;
      padding-top: 1rem;
      border-top: 1px solid #ddd;
      font-size: 0.8rem;
      color: #666;
      text-align: center;
    }
    .actions {
      margin-top: 1.5rem;
      text-align: center;
    }
    .btn-print {
      display: inline-block;
      padding: 0.5rem 1.25rem;
      background: #006131;
      color: #fff !important;
      border: none;
      border-radius: 6px;
      font-size: 0.95rem;
      cursor: pointer;
      text-decoration: none;
    }
    .btn-print:hover {
      background: #004d27;
    }
    @media print {
      body { padding: 0.5rem; }
      .no-print { display: none !important; }
      .wrap { max-width: none; }
      .header { border-bottom-color: #000; }
    }
    @page {
      margin: 12mm;
    }
  </style>
</head>
<body>
  <div class="wrap">
    <header class="header">
      <img
        class="logo"
        src="{{ asset('public/assets/img/logo.png') }}"
        alt="Logo {{ config('app.name', 'Omahjong') }}"
        width="72"
        height="72"
      />
      <div class="brand-meta">
        <h1>{{ config('app.name', 'Omahjong') }}</h1>
        <p>Kuitansi / Invoice pembayaran sewa meja</p>
      </div>
    </header>

    <p class="doc-title">Kuitansi pembayaran</p>

    <table class="meta">
      <tr>
        <th>No. kuitansi</th>
        <td>INV-{{ str_pad((string) $cashFlow->id, 6, '0', STR_PAD_LEFT) }}</td>
      </tr>
      <tr>
        <th>Tanggal</th>
        <td>{{ $cashFlow->waktu_pembayaran->translatedFormat('d F Y H:i') }}</td>
      </tr>
      @if ($cashFlow->rental)
        <tr>
          <th>Meja</th>
          <td>{{ $cashFlow->rental->meja->nama ?? '—' }}@if ($cashFlow->rental->meja && $cashFlow->rental->meja->toko) ({{ $cashFlow->rental->meja->toko->nama }})@endif</td>
        </tr>
        <tr>
          <th>Customer</th>
          <td>{{ $cashFlow->rental->nama_customer }}</td>
        </tr>
      @endif
      <tr>
        <th>Metode pembayaran</th>
        <td>{{ \App\Models\CashFlow::metodePembayaranLabel($cashFlow->metode_pembayaran) }}</td>
      </tr>
      <tr>
        <th>Status</th>
        <td><strong>Lunas</strong></td>
      </tr>
    </table>

    <div class="amount-box">
      @if ($cashFlow->jumlah_bayar !== null && (float) $cashFlow->jumlah_bayar !== (float) $cashFlow->total)
        <div class="label">Tagihan sewa</div>
        <div class="value" style="font-size:1rem;margin-bottom:0.5rem;">Rp {{ number_format((float) $cashFlow->total, 0, ',', '.') }}</div>
      @endif
      <div class="label">Jumlah dibayar</div>
      <div class="value">Rp {{ number_format($cashFlow->amountPaid(), 0, ',', '.') }}</div>
    </div>

    <p style="font-size:0.9rem;color:#444;margin:0;">
      Dokumen ini diterbitkan secara elektronik setelah pembayaran dan bukti transaksi dilengkapi di sistem arus kas.
    </p>

    <div class="footer">
      Terima kasih atas kunjungan Anda.
    </div>

    <div class="actions no-print">
      <button type="button" class="btn-print" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>
  </div>
</body>
</html>
