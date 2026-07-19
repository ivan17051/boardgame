@php
  $rental = $rental ?? null;
  $cashFlows = $cash_flows ?? collect();
  $fmtRp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
  $firstCf = $cashFlows->first();
  $waktuBayar = $firstCf ? $firstCf->waktu_pembayaran : ($rental->waktu_end ?? now());
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Kuitansi sewa #{{ str_pad((string) $rental->id, 6, '0', STR_PAD_LEFT) }} — {{ config('app.name', 'Omahjong') }}</title>
  <style>
    * { box-sizing: border-box; }
    body {
      font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      margin: 0;
      padding: 1.5rem;
      color: #1a1a1a;
      background: #fff;
      line-height: 1.45;
      font-size: 0.95rem;
    }
    .wrap { max-width: 680px; margin: 0 auto; }
    .header {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding-bottom: 1rem;
      border-bottom: 2px solid #006131;
      margin-bottom: 1.25rem;
    }
    .logo { width: 72px; height: 72px; object-fit: contain; flex-shrink: 0; }
    .brand-meta h1 { margin: 0 0 0.25rem; font-size: 1.25rem; font-weight: 700; color: #006131; }
    .brand-meta p { margin: 0; font-size: 0.85rem; color: #555; }
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
      margin-bottom: 1.25rem;
    }
    table.meta th,
    table.meta td {
      padding: 0.4rem 0;
      vertical-align: top;
      text-align: left;
    }
    table.meta th {
      width: 38%;
      color: #555;
      font-weight: 600;
    }
    table.lines {
      width: 100%;
      border-collapse: collapse;
      margin: 1rem 0;
      font-size: 0.9rem;
    }
    table.lines th,
    table.lines td {
      border: 1px solid #ccc;
      padding: 0.5rem 0.6rem;
      vertical-align: top;
    }
    table.lines th {
      background: #f1f3f5;
      font-weight: 600;
      text-align: left;
    }
    table.lines tfoot th,
    table.lines tfoot td {
      background: #f8f9fa;
      font-weight: 600;
    }
    .text-end { text-align: right; }
    .amount-box {
      background: #f4fbf7;
      border: 1px solid #c5e4d4;
      border-radius: 8px;
      padding: 1rem 1.25rem;
      margin: 1.25rem 0;
    }
    .amount-box .row-line {
      display: flex;
      justify-content: space-between;
      margin-bottom: 0.35rem;
      font-size: 0.9rem;
    }
    .amount-box .grand {
      font-size: 1.35rem;
      font-weight: 700;
      color: #006131;
      margin-top: 0.5rem;
      padding-top: 0.5rem;
      border-top: 1px solid #c5e4d4;
    }
    .footer {
      margin-top: 2rem;
      padding-top: 1rem;
      border-top: 1px solid #ddd;
      font-size: 0.8rem;
      color: #666;
      text-align: center;
    }
    .actions { margin-top: 1.5rem; text-align: center; }
    .btn-print {
      display: inline-block;
      padding: 0.5rem 1.25rem;
      background: #006131;
      color: #fff !important;
      border: none;
      border-radius: 6px;
      font-size: 0.95rem;
      cursor: pointer;
    }
    .btn-print:hover { background: #004d27; }
    .section-title {
      font-size: 0.85rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      color: #006131;
      margin: 1.25rem 0 0.5rem;
    }
    @media print {
      body { padding: 0.5rem; }
      .no-print { display: none !important; }
      .wrap { max-width: none; }
    }
    @page { margin: 12mm; }
  </style>
</head>
<body>
  <div class="wrap">
    <header class="header">
      <img class="logo" src="{{ asset('public/assets/img/logo.png') }}" alt="Logo" width="72" height="72" />
      <div class="brand-meta">
        <h1>{{ config('app.name', 'Omahjong') }}</h1>
        <p>Kuitansi pembayaran sewa meja</p>
      </div>
    </header>

    <p class="doc-title">Kuitansi / Invoice</p>

    <table class="meta">
      <tr>
        <th>No. transaksi</th>
        <td>RNT-{{ str_pad((string) $rental->id, 6, '0', STR_PAD_LEFT) }}</td>
      </tr>
      <tr>
        <th>Tanggal bayar</th>
        <td>{{ $waktuBayar->translatedFormat('d F Y H:i') }}</td>
      </tr>
      @if ($rental->id_meja)
        <tr>
          <th>Meja</th>
          <td>
            {{ $rental->meja->nama ?? '—' }}
            @if ($rental->meja && $rental->meja->toko)
              ({{ $rental->meja->toko->nama }})
            @endif
          </td>
        </tr>
      @endif
      <tr>
        <th>Nama customer</th>
        <td>({{ $rental->isMember() ? 'Member' : 'Non-Member' }}) {{ $rental->nama_customer }}</td>
      </tr>
      @if ($rental->id_meja && $rental->waktu_start && $rental->waktu_end)
        <tr>
          <th>Periode sewa</th>
          <td>
            {{ $rental->waktu_start->format('H:i') }}
            — {{ $rental->waktu_end->format('H:i') }}
          </td>
        </tr>
      @endif
      @if ($rental->id_meja && ($durasi_hms ?? null))
        <tr>
          <th>Durasi</th>
          <td>{{ $durasi_hms }} ({{ number_format($billed_hours, 2, ',', '.') }} jam)</td>
        </tr>
      @endif
      @if ($rental->id_meja)
        <tr>
          <th>Tarif normal</th>
          <td>{{ $fmtRp($rental->harga) }}/jam</td>
        </tr>
      @endif
      @if ($rental->id_meja && $rental->hasPromo())
        <tr>
          <th>Promo</th>
          <td>
            {{ $rental->promo_nama }}
            — {{ $fmtRp($rental->promo_hourly_rate) }}/jam
            @if ($rental->promo_tgl_awal || $rental->promo_tgl_akhir)
              ·
              @if ($rental->promo_tgl_awal && $rental->promo_tgl_akhir)
                {{ $rental->promo_tgl_awal->format('d/m/Y') }}–{{ $rental->promo_tgl_akhir->format('d/m/Y') }}
              @elseif ($rental->promo_tgl_awal)
                dari {{ $rental->promo_tgl_awal->format('d/m/Y') }}
              @else
                hingga {{ $rental->promo_tgl_akhir->format('d/m/Y') }}
              @endif
            @endif
            · jam {{ substr(\App\Models\RentalPromo::normalizeTimeString($rental->promo_jam_mulai), 0, 5) }}–{{ substr(\App\Models\RentalPromo::normalizeTimeString($rental->promo_jam_selesai), 0, 5) }}
            @if ($rental->hasPromoDurationLimit())
              (maks. {{ number_format((float) $rental->promo_duration_limit, 2, ',', '.') }} jam ditagihkan)
            @else
              (tanpa batas durasi, hingga jam selesai)
            @endif
          </td>
        </tr>
      @endif
      <tr>
        <th>Metode pembayaran</th>
        <td>{{ $metode_label }}</td>
      </tr>
      <tr>
        <th>Status</th>
        <td><strong>Lunas</strong></td>
      </tr>
    </table>

    <p class="section-title">Rincian tagihan</p>
    <table class="lines">
      <thead>
        <tr>
          <th>Uraian</th>
          <th class="text-end" style="width:56px">Qty</th>
          <th class="text-end" style="width:110px">Harga</th>
          <th class="text-end" style="width:120px">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        @if ($rental->id_meja)
          <tr>
            <td>
              Sewa meja
              @if ($billed_hours > 0)
                @if ($rental->hasPromo())
                  <span class="text-secondary">(durasi {{ number_format($billed_hours, 2, ',', '.') }} jam; promo {{ $rental->promo_nama }})</span>
                @else
                  <span class="text-secondary">({{ number_format($billed_hours, 2, ',', '.') }} jam × {{ $fmtRp($rental->harga) }}/jam)</span>
                @endif
              @endif
            </td>
            <td class="text-end">1</td>
            <td class="text-end font-monospace">{{ $fmtRp($total_harga_sewa) }}</td>
            <td class="text-end font-monospace">{{ $fmtRp($total_harga_sewa) }}</td>
          </tr>
        @endif
        @foreach ($rental->additionalItems as $line)
          <tr>
            <td>
              @if ((float) $line->subtotal < 0)
                Diskon — {{ $line->nama }}
              @else
                Additional — {{ $line->nama }}
              @endif
            </td>
            <td class="text-end">{{ $line->qty }}</td>
            <td class="text-end font-monospace">
              @if ((float) $line->subtotal < 0)
                − {{ $fmtRp($line->harga) }}
              @else
                {{ $fmtRp($line->harga) }}
              @endif
            </td>
            <td class="text-end font-monospace">{{ $fmtRp($line->subtotal) }}</td>
          </tr>
        @endforeach
      </tbody>
      <tfoot>
        @if ($rental->id_meja)
          <tr>
            <th colspan="3" class="text-end">Subtotal sewa meja</th>
            <td class="text-end font-monospace">{{ $fmtRp($total_harga_sewa) }}</td>
          </tr>
        @endif
        @if ($total_harga_additional != 0)
          <tr>
            <th colspan="3" class="text-end">Subtotal item tambahan &amp; diskon</th>
            <td class="text-end font-monospace">{{ $fmtRp($total_harga_additional) }}</td>
          </tr>
        @endif
        <tr>
          <th colspan="3" class="text-end">Total tagihan</th>
          <td class="text-end font-monospace">{{ $fmtRp($total_tagihan) }}</td>
        </tr>
      </tfoot>
    </table>


    <div class="amount-box">
      <div class="row-line">
        <span>Total tagihan</span>
        <span class="font-monospace">{{ $fmtRp($total_tagihan) }}</span>
      </div>
      <div class="grand d-flex justify-content-between">
        <span>Jumlah dibayar</span>
        <span class="font-monospace">{{ $fmtRp($total_dibayar) }}</span>
      </div>
    </div>

    <p style="font-size:0.85rem;color:#444;margin:0;">
      Dokumen ini diterbitkan secara elektronik setelah pembayaran lengkap di sistem.
    </p>

    <div class="footer">Terima kasih atas kunjungan Anda.</div>

    <div class="actions no-print">
      <button type="button" class="btn-print" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>
  </div>
</body>
</html>
