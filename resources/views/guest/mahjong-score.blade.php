<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <meta name="theme-color" content="#006131" />
  <title>Catat Poin Mahjong — Omahjong</title>
  <link rel="icon" type="image/png" href="{{ asset('public/assets/img/logo.png') }}" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous" />
  <link rel="stylesheet" href="{{ asset('public/css/adminlte.css') }}" />
  <style>
    :root {
      --score-brand: #006131;
      --score-brand-dark: #004d26;
    }
    body.guest-score-page {
      min-height: 100vh;
      min-height: 100dvh;
      background: linear-gradient(165deg, #f0f7f3 0%, #e8f0eb 45%, #f8faf9 100%);
    }
    .score-shell {
      max-width: 480px;
      margin: 0 auto;
      padding: 1rem 1rem 2.5rem;
    }
    .score-tabs {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.35rem;
      padding: 0.3rem;
      margin-bottom: 1rem;
      background: rgba(0, 97, 49, 0.08);
      border-radius: 0.85rem;
    }
    .score-tabs .score-tab {
      border: 0;
      border-radius: 0.65rem;
      background: transparent;
      color: #495057;
      font-weight: 600;
      padding: 0.65rem 0.5rem;
    }
    .score-tabs .score-tab.active {
      background: #fff;
      color: var(--score-brand-dark);
      box-shadow: 0 2px 8px rgba(0, 60, 30, 0.1);
    }
    .guide-card h2 {
      color: var(--score-brand-dark);
    }
    .guide-card h3 {
      font-size: 0.95rem;
      font-weight: 700;
      color: var(--score-brand-dark);
      margin: 1.15rem 0 0.5rem;
    }
    .guide-card h3:first-of-type {
      margin-top: 0;
    }
    .guide-card p,
    .guide-card li {
      font-size: 0.875rem;
    }
    .guide-card ul,
    .guide-card ol {
      padding-left: 1.25rem;
      margin-bottom: 0.75rem;
    }
    .guide-table {
      width: 100%;
      margin-bottom: 0.75rem;
      font-size: 0.8rem;
    }
    .guide-table th,
    .guide-table td {
      padding: 0.45rem 0.5rem;
      vertical-align: middle;
    }
    .guide-table thead th {
      background: #f3f8f5;
      color: var(--score-brand-dark);
    }
    .guide-note {
      border-left: 3px solid #ffc107;
      background: #fff8df;
      border-radius: 0.35rem;
      padding: 0.65rem 0.75rem;
      color: #664d03;
      font-size: 0.8rem;
    }
    .guide-meld-examples {
      display: grid;
      gap: 0.6rem;
      margin: 0.75rem 0 1rem;
    }
    .guide-meld {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.65rem;
      background: #f7faf8;
      border: 1px solid rgba(0, 97, 49, 0.1);
      border-radius: 0.75rem;
    }
    .guide-meld-label {
      flex: 0 0 5.2rem;
      font-size: 0.78rem;
      font-weight: 700;
      color: var(--score-brand-dark);
    }
    .guide-tiles {
      display: flex;
      align-items: center;
      gap: 0.2rem;
      min-width: max-content;
    }
    .guide-tile {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      width: 2.25rem;
      height: 3rem;
      background: linear-gradient(135deg, #fff 0%, #f5f2e9 100%);
      border: 1px solid #d5d1c5;
      border-bottom: 3px solid #c7c1b2;
      border-radius: 0.3rem;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
      line-height: 1;
    }
    .guide-tile .tile-number {
      font-size: 1.15rem;
      font-weight: 700;
    }
    .guide-tile .tile-suit {
      margin-top: 0.2rem;
      font-size: 0.7rem;
      font-weight: 700;
    }
    .guide-tile.bamboo {
      color: #087443;
    }
    .guide-tile.dots {
      color: #b33232;
    }
    .guide-tile.characters {
      color: #345b9a;
    }
    .guide-tile.honors {
      color: #a52e2e;
    }
    .guide-tile.honors.green-dragon {
      color: #087443;
    }
    .guide-tile.honors.white-dragon {
      color: #68716e;
    }
    .guide-example {
      margin: 0.75rem 0;
      padding: 0.75rem;
      background: #f7faf8;
      border: 1px solid rgba(0, 97, 49, 0.1);
      border-radius: 0.75rem;
    }
    .guide-example h4 {
      font-size: 0.88rem;
      font-weight: 700;
      color: var(--score-brand-dark);
      margin: 0 0 0.35rem;
    }
    .guide-example .example-caption {
      font-size: 0.75rem;
      color: #6c757d;
      margin: 0 0 0.6rem;
    }
    .guide-winning-hand {
      display: flex;
      align-items: flex-start;
      gap: 0.55rem;
      overflow-x: auto;
      padding: 0.15rem 0 0.45rem;
      scrollbar-width: thin;
    }
    .guide-hand-group {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.25rem;
      flex: 0 0 auto;
    }
    .guide-hand-group .guide-tiles {
      gap: 0.12rem;
    }
    .guide-hand-group .guide-tile {
      width: 1.8rem;
      height: 2.5rem;
    }
    .guide-hand-group .tile-number {
      font-size: 0.95rem;
    }
    .guide-hand-group .tile-suit {
      margin-top: 0.15rem;
      font-size: 0.6rem;
    }
    .guide-hand-group small {
      font-size: 0.62rem;
      color: #6c757d;
      white-space: nowrap;
    }
    .guide-example .example-score {
      border-top: 1px solid rgba(0, 97, 49, 0.1);
      padding-top: 0.5rem;
      margin-top: 0.45rem;
      font-size: 0.78rem;
    }
    .score-brand {
      text-align: center;
      margin-bottom: 1rem;
    }
    .score-brand img {
      height: 40px;
      width: auto;
    }
    .score-brand h1 {
      font-size: 1.2rem;
      font-weight: 700;
      color: var(--score-brand);
      margin: 0.35rem 0 0;
    }
    .score-meta {
      text-align: center;
      color: #6c757d;
      font-size: 0.875rem;
      margin-bottom: 1rem;
    }
    .score-card {
      background: #fff;
      border-radius: 1rem;
      border: 1px solid rgba(0, 97, 49, 0.12);
      box-shadow: 0 8px 32px rgba(0, 60, 30, 0.08);
      padding: 1rem;
      margin-bottom: 1rem;
    }
    .score-card h2 {
      font-size: 1rem;
      font-weight: 700;
      margin: 0 0 0.75rem;
    }
    .totals-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.6rem;
    }
    .total-chip {
      background: #f3f8f5;
      border-radius: 0.75rem;
      padding: 0.65rem 0.75rem;
      border: 1px solid rgba(0, 97, 49, 0.08);
    }
    .total-chip .name {
      font-size: 0.8rem;
      color: #495057;
      margin-bottom: 0.15rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .total-chip .pts {
      font-size: 1.35rem;
      font-weight: 700;
      font-variant-numeric: tabular-nums;
      color: var(--score-brand-dark);
    }
    .seat-row {
      display: grid;
      grid-template-columns: 2.2rem 1fr;
      gap: 0.5rem;
      align-items: center;
      margin-bottom: 0.55rem;
    }
    .seat-row .seat-no {
      width: 2.2rem;
      height: 2.2rem;
      border-radius: 999px;
      background: var(--score-brand);
      color: #fff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 0.85rem;
    }
    .seat-row input {
      font-size: 1.05rem;
      min-height: 2.6rem;
    }
    .score-player-row {
      display: grid;
      grid-template-columns: 2.2rem 1fr;
      gap: 0.5rem;
      align-items: start;
      margin-bottom: 0.75rem;
    }
    .score-player-name {
      font-weight: 700;
      font-size: 0.95rem;
      color: var(--score-brand-dark);
      margin-bottom: 0.2rem;
    }
    .score-player-poin-label {
      font-size: 0.78rem;
      color: #6c757d;
      margin-bottom: 0.2rem;
    }
    .history-table {
      width: 100%;
      margin: 0;
      font-size: 0.8rem;
    }
    .history-table th,
    .history-table td {
      padding: 0.45rem 0.35rem;
      vertical-align: middle;
      text-align: center;
      font-variant-numeric: tabular-nums;
    }
    .history-table th:first-child,
    .history-table td:first-child {
      text-align: left;
      white-space: nowrap;
    }
    .history-table thead th {
      background: #f3f8f5;
      font-weight: 700;
      color: var(--score-brand-dark);
      border-bottom: 1px solid rgba(0, 97, 49, 0.12);
    }
    .history-table tbody tr.voided {
      opacity: 0.5;
      text-decoration: line-through;
    }
    .history-table .is-winner {
      font-weight: 700;
      color: var(--score-brand-dark);
      background: rgba(255, 193, 7, 0.18);
    }
    .history-table .history-poin {
      width: 4.25rem;
      max-width: 100%;
      margin: 0 auto;
      text-align: center;
      font-variant-numeric: tabular-nums;
      padding: 0.25rem 0.3rem;
      min-height: 2.1rem;
    }
    .history-table .is-winner .history-poin {
      background: #fffdf3;
      border-color: #ffc107;
    }
    .history-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.35rem;
    }
    .reset-overlay {
      position: fixed;
      inset: 0;
      background: rgba(15, 30, 22, 0.55);
      backdrop-filter: blur(3px);
      display: none;
      align-items: center;
      justify-content: center;
      padding: 1rem;
      z-index: 1080;
    }
    .reset-overlay.show {
      display: flex;
    }
    .reset-dialog {
      background: #fff;
      border-radius: 1rem;
      box-shadow: 0 20px 60px rgba(0, 40, 20, 0.25);
      width: 100%;
      max-width: 380px;
      padding: 1.15rem 1.2rem 1.2rem;
    }
    .reset-dialog h3 {
      font-size: 1.05rem;
      font-weight: 700;
      color: var(--score-brand-dark);
      margin: 0 0 0.5rem;
    }
    .winner-toggle {
      display: flex;
      flex-wrap: wrap;
      gap: 0.4rem;
      margin: 0.5rem 0 0.75rem;
    }
    .winner-toggle .btn {
      flex: 1 1 calc(50% - 0.4rem);
      min-height: 2.4rem;
    }
    .alert-slot:empty {
      display: none;
    }
    .readonly-banner {
      background: #fff3cd;
      color: #664d03;
      border-radius: 0.75rem;
      padding: 0.65rem 0.85rem;
      font-size: 0.875rem;
      margin-bottom: 1rem;
      border: 1px solid #ffecb5;
    }
    .token-missing {
      text-align: center;
      padding: 2rem 1rem;
    }
  </style>
</head>
<body class="guest-score-page">
  <div class="score-shell">
    <div class="score-brand">
      <img src="{{ asset('public/assets/img/logo.png') }}" alt="Omahjong" />
      <h1>Catat Poin Mahjong</h1>
    </div>

    <div class="score-tabs" role="tablist" aria-label="Halaman skor">
      <button type="button" class="score-tab active" id="scoreTab" role="tab" aria-selected="true" aria-controls="scoreEntryPanel" tabindex="0">
        <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Catat skor
      </button>
      <button type="button" class="score-tab" id="guideTab" role="tab" aria-selected="false" aria-controls="scoreGuide" tabindex="-1">
        <i class="bi bi-journal-text me-1" aria-hidden="true"></i>Panduan
      </button>
    </div>

    <div id="scoreEntryPanel" class="score-tab-pane active" role="tabpanel" aria-labelledby="scoreTab" tabindex="0">
      <div id="tokenMissing" class="score-card token-missing d-none">
        <p class="mb-2">Link skor tidak valid.</p>
        <p class="small text-secondary mb-0">Minta kasir membuka ulang <strong>Link skor</strong> untuk meja Anda.</p>
      </div>

    <div id="scoreApp" class="d-none">
      <div class="score-meta" id="scoreMeta"></div>
      <div id="readonlyBanner" class="readonly-banner d-none">
        Sewa sudah selesai. Skor hanya bisa dilihat (tidak bisa diubah).
      </div>
      <div class="alert-slot alert alert-danger py-2" id="errorSlot"></div>
      <div class="alert-slot alert alert-success py-2" id="successSlot"></div>

      <div class="score-card" id="setupCard">
        <h2>Nama pemain</h2>
        <p class="small text-secondary mb-2">Isi 4 nama / julukan di meja ini.</p>
        <div id="playerInputs"></div>
        <button type="button" class="btn btn-success w-100 mt-2" id="savePlayersBtn">
          Simpan nama pemain
        </button>
      </div>

      <div class="score-card d-none" id="totalsCard">
        <h2>Total poin</h2>
        <div class="totals-grid" id="totalsGrid"></div>
      </div>

      <div class="score-card d-none" id="handCard">
        <h2>Ronde baru</h2>
        <div id="scoreInputs"></div>
        <div class="small text-secondary mb-1">Pemenang dipilih otomatis dari skor tertinggi</div>
        <div class="winner-toggle" id="winnerToggle"></div>
        <button type="button" class="btn btn-primary w-100" id="saveHandBtn">
          <i class="bi bi-plus-lg me-1"></i>Simpan ronde
        </button>
      </div>

      <div class="score-card d-none" id="historyCard">
        <div class="d-flex justify-content-between align-items-center mb-2 gap-2">
          <h2 class="mb-0">Riwayat ronde</h2>
          <div class="history-actions">
            <button type="button" class="btn btn-sm btn-outline-danger d-none" id="voidLastBtn">
              Batalkan terakhir
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="resetBtn">
              Reset
            </button>
          </div>
        </div>
        <div class="table-responsive">
          <table class="history-table d-none" id="handTable">
            <thead>
              <tr id="handTableHead"></tr>
            </thead>
            <tbody id="handTableBody"></tbody>
          </table>
        </div>
        <p class="small text-secondary mb-0 d-none" id="handEmpty">Belum ada ronde.</p>
        <p class="small text-secondary mb-0 mt-2 d-none" id="handEditHint">Ketuk skor di tabel untuk mengubah ronde yang sudah tersimpan.</p>
      </div>
    </div>
    </div>

    <section id="scoreGuide" class="score-tab-pane" role="tabpanel" aria-labelledby="guideTab" tabindex="0" hidden>
      <div class="score-card guide-card">
        <h2>Panduan Mahjong Bamboo</h2>
        <p class="small text-secondary">Ringkasan aturan turnamen Threefrogs Bamboo Mahjong.</p>

        <h3>Cara bermain</h3>
        <ul>
          <li>Susun tangan menang dari 4 set dan 1 pasangan (umumnya 14 ubin).</li>
          <li>Giliran berjalan berlawanan arah jarum jam: ambil satu ubin dari dinding, lalu buang satu ubin ke river.</li>
          <li><strong>Pong:</strong> klaim buangan untuk membentuk tiga ubin identik.</li>
          <li><strong>Kong:</strong> klaim buangan untuk membentuk empat ubin identik, lalu ambil ubin pengganti dari sea.</li>
          <li><strong>Chii:</strong> klaim buangan untuk membuat urutan, hanya dari pemain di sebelah kiri.</li>
          <li><strong>Ron / Hu:</strong> menang dengan mengklaim ubin buangan yang melengkapi tangan. <strong>Tsumo:</strong> menang dengan mengambil ubin pelengkap dari dinding.</li>
        </ul>

        <h3>Contoh set ubin</h3>
        <p class="small text-secondary mb-2">Setiap ilustrasi menunjukkan ubin yang membentuk satu kelompok.</p>
        <div class="guide-meld-examples" aria-label="Ilustrasi pasangan, urutan, pong, dan kong">
          <div class="guide-meld">
            <span class="guide-meld-label">Urutan<br /><small>(Chii)</small></span>
            <div class="guide-tiles" role="img" aria-label="2, 3, 4 bambu">
              <span class="guide-tile bamboo"><span class="tile-number">2</span><span class="tile-suit">索</span></span>
              <span class="guide-tile bamboo"><span class="tile-number">3</span><span class="tile-suit">索</span></span>
              <span class="guide-tile bamboo"><span class="tile-number">4</span><span class="tile-suit">索</span></span>
            </div>
          </div>
          <div class="guide-meld">
            <span class="guide-meld-label">Triplet<br /><small>(Pong)</small></span>
            <div class="guide-tiles" role="img" aria-label="Tiga ubin 5 lingkaran">
              <span class="guide-tile dots"><span class="tile-number">5</span><span class="tile-suit">筒</span></span>
              <span class="guide-tile dots"><span class="tile-number">5</span><span class="tile-suit">筒</span></span>
              <span class="guide-tile dots"><span class="tile-number">5</span><span class="tile-suit">筒</span></span>
            </div>
          </div>
          <div class="guide-meld">
            <span class="guide-meld-label">Empat identik<br /><small>(Kong)</small></span>
            <div class="guide-tiles" role="img" aria-label="Empat ubin 8 karakter">
              <span class="guide-tile characters"><span class="tile-number">8</span><span class="tile-suit">萬</span></span>
              <span class="guide-tile characters"><span class="tile-number">8</span><span class="tile-suit">萬</span></span>
              <span class="guide-tile characters"><span class="tile-number">8</span><span class="tile-suit">萬</span></span>
              <span class="guide-tile characters"><span class="tile-number">8</span><span class="tile-suit">萬</span></span>
            </div>
          </div>
          <div class="guide-meld">
            <span class="guide-meld-label">Pasangan</span>
            <div class="guide-tiles" role="img" aria-label="Dua ubin naga putih">
              <span class="guide-tile honors white-dragon"><span class="tile-number">白</span><span class="tile-suit">Naga</span></span>
              <span class="guide-tile honors white-dragon"><span class="tile-number">白</span><span class="tile-suit">Naga</span></span>
            </div>
          </div>
        </div>

        <h3>Nilai set</h3>
        <p class="mb-2">Nilai tangan dasar = <strong>1 base point + total nilai set + bonus pola khusus</strong>. Simples adalah ubin angka 2–8; terminal adalah angka 1 dan 9; honor adalah ubin angin dan naga.</p>
        <div class="table-responsive">
          <table class="table table-sm table-bordered guide-table">
            <thead>
              <tr><th>Set</th><th>Simples</th><th>Terminal &amp; Honor</th></tr>
            </thead>
            <tbody>
              <tr><td>Pasangan</td><td>0</td><td>+1</td></tr>
              <tr><td>Pong (tiga identik)</td><td>+1</td><td>+2</td></tr>
              <tr><td>Kong (empat identik)</td><td>+2</td><td>+4</td></tr>
            </tbody>
          </table>
        </div>

        <h3>Bonus pola khusus</h3>
        <div class="table-responsive">
          <table class="table table-sm table-bordered guide-table">
            <thead>
              <tr><th>Pola</th><th>Bonus</th><th>Kriteria</th></tr>
            </thead>
            <tbody>
              <tr><td>All Simples</td><td>+1</td><td>Tidak ada terminal atau honor.</td></tr>
              <tr><td>Closed Hand</td><td>+2</td><td>Menang tanpa memanggil ubin dari lawan.</td></tr>
              <tr><td>All Sequence</td><td>+2</td><td>Empat urutan dan satu pasangan; tanpa honor atau triplet.</td></tr>
              <tr><td>All Triplets</td><td>+6</td><td>Empat triplet dan satu pasangan.</td></tr>
              <tr><td>Half-Flush</td><td>+6</td><td>Satu jenis ubin angka dan ubin honor.</td></tr>
              <tr><td>Seven Pairs</td><td>+7</td><td>Tujuh pasangan berbeda.</td></tr>
              <tr><td>Full Flush</td><td>+13</td><td>Satu jenis ubin angka tanpa honor.</td></tr>
            </tbody>
          </table>
        </div>

        <h3>Pembayaran saat menang</h3>
        <ul>
          <li><strong>Ron / Hu:</strong> pemenang menerima 2× nilai tangan dari pemain yang membuang ubin kemenangan; pemain lain tidak membayar.</li>
          <li><strong>Tsumo / Zi Mo:</strong> pemenang menerima 1× nilai tangan dari masing-masing pemain lain.</li>
        </ul>
        <h3>Contoh tangan menang dari dokumen</h3>
        <p class="small text-secondary mb-2">Kelompokkan 4 set dan 1 pasangan seperti contoh berikut. Geser ke samping untuk melihat seluruh tangan di layar kecil.</p>
        <div class="guide-example">
          <h4>Ron / Hu</h4>
          <p class="example-caption">Dua pong simples, satu pong honor, satu urutan, dan satu pasangan honor.</p>
          <div class="guide-winning-hand" role="group" aria-label="Contoh tangan Ron: dua pong bambu, pong naga merah, urutan karakter, dan pasangan naga putih">
            <div class="guide-hand-group">
              <div class="guide-tiles" aria-hidden="true">
                <span class="guide-tile bamboo"><span class="tile-number">2</span><span class="tile-suit">索</span></span><span class="guide-tile bamboo"><span class="tile-number">2</span><span class="tile-suit">索</span></span><span class="guide-tile bamboo"><span class="tile-number">2</span><span class="tile-suit">索</span></span>
              </div>
              <small>Pong +1</small>
            </div>
            <div class="guide-hand-group">
              <div class="guide-tiles" aria-hidden="true">
                <span class="guide-tile bamboo"><span class="tile-number">7</span><span class="tile-suit">索</span></span><span class="guide-tile bamboo"><span class="tile-number">7</span><span class="tile-suit">索</span></span><span class="guide-tile bamboo"><span class="tile-number">7</span><span class="tile-suit">索</span></span>
              </div>
              <small>Pong +1</small>
            </div>
            <div class="guide-hand-group">
              <div class="guide-tiles" aria-hidden="true">
                <span class="guide-tile honors"><span class="tile-number">中</span><span class="tile-suit">Naga</span></span><span class="guide-tile honors"><span class="tile-number">中</span><span class="tile-suit">Naga</span></span><span class="guide-tile honors"><span class="tile-number">中</span><span class="tile-suit">Naga</span></span>
              </div>
              <small>Pong +2</small>
            </div>
            <div class="guide-hand-group">
              <div class="guide-tiles" aria-hidden="true">
                <span class="guide-tile characters"><span class="tile-number">2</span><span class="tile-suit">萬</span></span><span class="guide-tile characters"><span class="tile-number">3</span><span class="tile-suit">萬</span></span><span class="guide-tile characters"><span class="tile-number">4</span><span class="tile-suit">萬</span></span>
              </div>
              <small>Urutan +0</small>
            </div>
            <div class="guide-hand-group">
              <div class="guide-tiles" aria-hidden="true">
                <span class="guide-tile honors white-dragon"><span class="tile-number">白</span><span class="tile-suit">Naga</span></span><span class="guide-tile honors white-dragon"><span class="tile-number">白</span><span class="tile-suit">Naga</span></span>
              </div>
              <small>Pasangan +1</small>
            </div>
          </div>
          <p class="example-score mb-0"><strong>1 base + 5 nilai set = 6.</strong> Ron membayar 2×6 = <strong>12 poin</strong>.</p>
        </div>
        <div class="guide-example">
          <h4>Tsumo / Zi Mo</h4>
          <p class="example-caption">Empat pong, satu pasangan, All Triplets, dan Closed Hand.</p>
          <div class="guide-winning-hand" role="group" aria-label="Contoh tangan Tsumo: dua pong bambu, pong naga merah, pong naga hijau, dan pasangan lingkaran">
            <div class="guide-hand-group">
              <div class="guide-tiles" aria-hidden="true">
                <span class="guide-tile bamboo"><span class="tile-number">2</span><span class="tile-suit">索</span></span><span class="guide-tile bamboo"><span class="tile-number">2</span><span class="tile-suit">索</span></span><span class="guide-tile bamboo"><span class="tile-number">2</span><span class="tile-suit">索</span></span>
              </div>
              <small>Pong +1</small>
            </div>
            <div class="guide-hand-group">
              <div class="guide-tiles" aria-hidden="true">
                <span class="guide-tile bamboo"><span class="tile-number">7</span><span class="tile-suit">索</span></span><span class="guide-tile bamboo"><span class="tile-number">7</span><span class="tile-suit">索</span></span><span class="guide-tile bamboo"><span class="tile-number">7</span><span class="tile-suit">索</span></span>
              </div>
              <small>Pong +1</small>
            </div>
            <div class="guide-hand-group">
              <div class="guide-tiles" aria-hidden="true">
                <span class="guide-tile honors"><span class="tile-number">中</span><span class="tile-suit">Naga</span></span><span class="guide-tile honors"><span class="tile-number">中</span><span class="tile-suit">Naga</span></span><span class="guide-tile honors"><span class="tile-number">中</span><span class="tile-suit">Naga</span></span>
              </div>
              <small>Pong +2</small>
            </div>
            <div class="guide-hand-group">
              <div class="guide-tiles" aria-hidden="true">
                <span class="guide-tile honors green-dragon"><span class="tile-number">發</span><span class="tile-suit">Naga</span></span><span class="guide-tile honors green-dragon"><span class="tile-number">發</span><span class="tile-suit">Naga</span></span><span class="guide-tile honors green-dragon"><span class="tile-number">發</span><span class="tile-suit">Naga</span></span>
              </div>
              <small>Pong +2</small>
            </div>
            <div class="guide-hand-group">
              <div class="guide-tiles" aria-hidden="true">
                <span class="guide-tile dots"><span class="tile-number">5</span><span class="tile-suit">筒</span></span><span class="guide-tile dots"><span class="tile-number">5</span><span class="tile-suit">筒</span></span>
              </div>
              <small>Pasangan +0</small>
            </div>
          </div>
          <p class="example-score mb-0"><strong>1 base + 6 nilai set + 8 bonus (All Triplets 6, Closed Hand 2) = 15.</strong> Tsumo membayar 15 poin dari masing-masing 3 pemain, total <strong>45 poin</strong>.</p>
        </div>

        <h3>Format turnamen &amp; penalti</h3>
        <ul>
          <li>Permainan berlangsung sampai batas waktu 50 menit atau 2 putaran penuh. Skor awal setiap peserta adalah 0.</li>
          <li>Jika dinding habis tanpa pemenang, pemain Tenpai menerima 5 poin dari setiap pemain yang tidak Tenpai.</li>
          <li>Uma di akhir ronde: peringkat 1 sampai 4 mendapat +15, +5, −5, dan −15 poin.</li>
          <li>Panggilan Chi/Pong ilegal setelah set terbuka dapat menyebabkan Dead Hand: tidak bisa menang pada ronde itu.</li>
          <li>Merobohkan 7 ubin atau lebih dari dinding: penalti 5 poin kepada setiap pemain lain. Permainan lambat berulang dapat dikenai penalti serupa setelah peringatan.</li>
          <li>Jika beberapa pemain menyatakan Hu pada satu buangan, hanya pemain terdekat berikutnya sesuai urutan giliran yang menang.</li>
          <li>Kecurangan yang disengaja dapat menyebabkan diskualifikasi.</li>
        </ul>
        <div class="guide-note">
          <strong>Catatan Chombo:</strong> dokumen menyebut dua ketentuan berbeda untuk klaim menang yang salah: penalti 10 poin kepada setiap lawan pada bagian pelanggaran, serta 30 poin dibagi di antara pemain lain pada bagian etiket. Konfirmasikan penerapan yang berlaku kepada panitia.
        </div>
        <p class="small text-secondary mt-3 mb-0">Aturan ini menggunakan House Rule Threefrogs dengan referensi Riichi yang disederhanakan. Hal yang belum diatur mengikuti Bamboo Tournament atau World Riichi Rules; keputusan penyelenggara bersifat final.</p>
      </div>
      <div class="score-card guide-card mt-3">
        <h2>Panduan tambahan: Belajar Mahjong</h2>
        <p class="small text-secondary">Sumber: MahjongGuide.pdf dari Toko Mahjong / PONG Mahjong Parlor Surabaya. Panduan ini memakai sistem poin tersendiri, berbeda dari aturan Bamboo di atas. Sepakati sistem yang digunakan sebelum bermain.</p>
        <a class="btn btn-sm btn-outline-success mb-3" href="{{ asset('public/assets/docs/MahjongGuide.pdf') }}" target="_blank" rel="noopener">Buka panduan asli (PDF)</a>

        <h3>Komponen &amp; cara bermain</h3>
        <ul>
          <li>Dimainkan oleh 4 orang. Tiga jenis ubin angka adalah bulat (Tongzi), karakter (Wanzi), dan bambu (Suozi), masing-masing bernomor 1 sampai 9.</li>
          <li>Ubin royal terdiri dari angin Timur, Selatan, Barat, Utara serta naga merah, hijau, dan putih. Terminal adalah ubin angka 1 dan 9.</li>
          <li>Ambil 1 ubin dan buang 1 ubin sampai ada yang menang. Tangan menang umumnya terdiri dari <strong>4 set dan 1 pasangan</strong>, dengan nilai minimal <strong>600 poin</strong>.</li>
          <li><strong>Chow:</strong> urutan 3 angka dalam jenis yang sama; hanya boleh mengklaim buangan pemain di sebelah kiri.</li>
          <li><strong>Pong:</strong> 3 ubin identik; dapat mengklaim buangan pemain mana pun.</li>
          <li><strong>Kong:</strong> 4 ubin identik; dapat berasal dari buangan pemain mana pun atau dikumpulkan sendiri.</li>
          <li>Jika tinggal menunggu 1 ubin untuk menang, klaim buangan dengan <strong>Hu</strong> atau ambil sendiri dari tembok dengan <strong>Zi Mo</strong>.</li>
        </ul>

        <h3>Pola &amp; nilai poin</h3>
        <p class="small text-secondary mb-2">Istilah ?logo? pada dokumen berarti jenis ubin angka. Nilai berikut mengikuti kelompok poin pada panduan asli.</p>
        <div class="table-responsive">
          <table class="table table-sm table-bordered guide-table">
            <thead><tr><th>Poin</th><th>Pola</th><th>Kriteria</th></tr></thead>
            <tbody>
              <tr><td>300</td><td>Set Mulia</td><td>Triplet/quad naga atau angin sesuai tempat duduk.</td></tr>
              <tr><td>300</td><td>Petapa</td><td>Menang dengan tangan sepenuhnya tertutup atau sepenuhnya terbuka.</td></tr>
              <tr><td>300</td><td>Tengah Murni</td><td>Hanya ubin angka 2 sampai 8, tanpa royal.</td></tr>
              <tr><td>300</td><td>Urutan Murni (Pinhu)</td><td>Semua set berupa urutan, tanpa kartu mulia.</td></tr>
              <tr><td>300</td><td>Urutan Berantai</td><td>Dua urutan sejenis dengan angka akhir set pertama menjadi angka awal set kedua, misalnya 123 dan 345. Satu set hanya boleh dipakai dalam satu urutan berantai.</td></tr>
              <tr><td>600</td><td>Ujung Separuh</td><td>Setiap set dan pasangan mengandung terminal atau royal; minimal satu set berupa urutan.</td></tr>
              <tr><td>1.200</td><td>Triplet Murni</td><td>Semua set berupa triplet atau quad.</td></tr>
              <tr><td>1.200</td><td>Lima Serangkai</td><td>Mengandung kelima kelompok: bambu, bulat, karakter, naga, dan angin.</td></tr>
              <tr><td>1.200</td><td>Serupa Separuh</td><td>Hanya satu jenis ubin angka dan ubin royal.</td></tr>
              <tr><td>1.200</td><td>Urutan Mirip</td><td>Tiga urutan dengan angka sama pada ketiga jenis ubin, misalnya 234 karakter, 234 bulat, dan 234 bambu.</td></tr>
              <tr><td>1.200</td><td>Urutan Lengkap Murni</td><td>Memiliki urutan 123, 456, dan 789 dalam satu jenis ubin.</td></tr>
              <tr><td>2.400</td><td>Komodo (Naga Kecil)</td><td>Dua triplet/quad naga dan satu pasangan naga.</td></tr>
              <tr><td>2.400</td><td>Ujung Murni</td><td>Semua set yang dipakai untuk menang mengandung terminal.</td></tr>
              <tr><td>3.900</td><td>Tujuh Keajaiban</td><td>Tujuh pasangan berbeda; tidak boleh ada quad.</td></tr>
              <tr><td>5.400</td><td>Serupa Murni (Satu Warna)</td><td>Hanya satu jenis ubin angka.</td></tr>
              <tr><td>12.000</td><td>Nantaboga (3 Naga Besar)</td><td>Triplet/quad ketiga naga.</td></tr>
              <tr><td>12.000</td><td>Topan (4 Angin Besar/Kecil)</td><td>Empat triplet/quad angin, atau tiga triplet/quad angin dan satu pasangan angin.</td></tr>
              <tr><td>12.000</td><td>Royal Murni</td><td>Tangan hanya terdiri dari ubin angin dan naga.</td></tr>
              <tr><td>12.000</td><td>Terminal Murni</td><td>Hanya ubin angka 1 dan 9.</td></tr>
              <tr><td>12.000</td><td>Quad Murni</td><td>Memiliki empat quad.</td></tr>
              <tr><td>12.000</td><td>13 Rakyat</td><td>Ubin 1 dan 9 dari ketiga jenis, semua naga, dan semua angin, dengan satu ubin sebagai pasangan.</td></tr>
              <tr><td>12.000</td><td>7 Tangga Surga</td><td>Tujuh pasangan berurutan dalam satu jenis ubin.</td></tr>
            </tbody>
          </table>
        </div>

        <h3>Cara pembayaran poin</h3>
        <ul>
          <li><strong>Hu:</strong> pemain yang membuang ubin kemenangan membayar nilai komposisi tangan. Dua pemain lainnya masing-masing membayar 300 poin.</li>
          <li>Contoh: Aming menang Hu senilai 2.400 dari Budi. Budi membayar 2.400, Caca dan Doni masing-masing 300; Aming menerima total <strong>3.000 poin</strong>.</li>
          <li><strong>Zi Mo:</strong> nilai poin dikalikan 2, lalu pembayaran dibagi rata kepada ketiga pemain lain.</li>
        </ul>
        <div class="guide-note">Nilai dan pembayaran di bagian ini adalah referensi panduan tambahan. Catat hasil sesuai sistem yang telah disepakati oleh semua pemain.</div>
      </div>
    </section>
  </div>

  <div class="reset-overlay" id="resetOverlay" aria-hidden="true">
    <div class="reset-dialog" role="dialog" aria-modal="true" aria-labelledby="resetTitle">
      <h3 id="resetTitle"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset skor</h3>
      <p class="small text-secondary mb-3">Semua ronde akan dihapus dan total kembali ke 0.</p>
      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="resetPlayersCheck" />
        <label class="form-check-label" for="resetPlayersCheck">Reset nama pemain juga</label>
      </div>
      <div class="d-grid gap-2">
        <button type="button" class="btn btn-danger" id="resetConfirmBtn">Reset</button>
        <button type="button" class="btn btn-outline-secondary" id="resetCancelBtn">Batal</button>
      </div>
    </div>
  </div>

  <script>
  (function () {
    const tabs = Array.from(document.querySelectorAll('.score-tabs [role="tab"]'));
    const panels = tabs.map(function (tab) {
      return document.getElementById(tab.getAttribute('aria-controls'));
    });

    function activateTab(index, moveFocus) {
      tabs.forEach(function (tab, tabIndex) {
        const selected = tabIndex === index;
        tab.classList.toggle('active', selected);
        tab.setAttribute('aria-selected', selected ? 'true' : 'false');
        tab.tabIndex = selected ? 0 : -1;
        panels[tabIndex].hidden = !selected;
        panels[tabIndex].classList.toggle('active', selected);
      });

      if (moveFocus) tabs[index].focus();
    }

    tabs.forEach(function (tab, index) {
      tab.addEventListener('click', function () {
        activateTab(index, false);
      });
      tab.addEventListener('keydown', function (event) {
        let nextIndex = index;
        if (event.key === 'ArrowRight') nextIndex = (index + 1) % tabs.length;
        else if (event.key === 'ArrowLeft') nextIndex = (index - 1 + tabs.length) % tabs.length;
        else if (event.key === 'Home') nextIndex = 0;
        else if (event.key === 'End') nextIndex = tabs.length - 1;
        else return;
        event.preventDefault();
        activateTab(nextIndex, true);
      });
    });
  })();

  (function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const STORAGE_KEY = 'omahjong_score_token';
    const routes = {
      show: @json(route('guest.mahjong-score.show')),
      players: @json(route('guest.mahjong-score.players')),
      storeHand: @json(route('guest.mahjong-score.hands.store')),
      voidLast: @json(route('guest.mahjong-score.hands.void-last')),
      updateHand: @json(route('guest.mahjong-score.hands.update', ['hand' => 0])),
      reset: @json(route('guest.mahjong-score.reset')),
    };
    const initialToken = @json($initialToken);

    let token = initialToken || localStorage.getItem(STORAGE_KEY) || '';
    if (initialToken) {
      localStorage.setItem(STORAGE_KEY, initialToken);
      token = initialToken;
    }

    let state = null;
    let winnerSeat = null;

    const els = {
      tokenMissing: document.getElementById('tokenMissing'),
      app: document.getElementById('scoreApp'),
      meta: document.getElementById('scoreMeta'),
      readonly: document.getElementById('readonlyBanner'),
      error: document.getElementById('errorSlot'),
      success: document.getElementById('successSlot'),
      setupCard: document.getElementById('setupCard'),
      playerInputs: document.getElementById('playerInputs'),
      savePlayersBtn: document.getElementById('savePlayersBtn'),
      totalsCard: document.getElementById('totalsCard'),
      totalsGrid: document.getElementById('totalsGrid'),
      handCard: document.getElementById('handCard'),
      scoreInputs: document.getElementById('scoreInputs'),
      winnerToggle: document.getElementById('winnerToggle'),
      saveHandBtn: document.getElementById('saveHandBtn'),
      historyCard: document.getElementById('historyCard'),
      handTable: document.getElementById('handTable'),
      handTableHead: document.getElementById('handTableHead'),
      handTableBody: document.getElementById('handTableBody'),
      handEmpty: document.getElementById('handEmpty'),
      handEditHint: document.getElementById('handEditHint'),
      voidLastBtn: document.getElementById('voidLastBtn'),
      resetBtn: document.getElementById('resetBtn'),
      resetOverlay: document.getElementById('resetOverlay'),
      resetPlayersCheck: document.getElementById('resetPlayersCheck'),
      resetConfirmBtn: document.getElementById('resetConfirmBtn'),
      resetCancelBtn: document.getElementById('resetCancelBtn'),
    };

    function clearAlerts() {
      els.error.textContent = '';
      els.success.textContent = '';
    }

    function showError(msg) {
      els.success.textContent = '';
      els.error.textContent = msg || 'Terjadi kesalahan.';
    }

    function showSuccess(msg) {
      els.error.textContent = '';
      els.success.textContent = msg || '';
    }

    function api(url, options) {
      options = options || {};
      const headers = Object.assign({
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Score-Token': token,
      }, options.headers || {});
      if (options.body && !(options.body instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(options.body);
      }
      const sep = url.indexOf('?') >= 0 ? '&' : '?';
      return fetch(url + sep + 'token=' + encodeURIComponent(token), Object.assign({}, options, { headers: headers }))
        .then(function (res) {
          return res.json().then(function (body) {
            return { ok: res.ok, status: res.status, body: body };
          }).catch(function () {
            return { ok: res.ok, status: res.status, body: null };
          });
        });
    }

    function firstError(body) {
      if (!body) return 'Gagal.';
      if (body.message && !body.errors) return body.message;
      if (body.errors) {
        const keys = Object.keys(body.errors);
        if (keys.length) return body.errors[keys[0]][0];
      }
      return body.message || 'Gagal.';
    }

    function renderSetup(playersReady, playersLocked, canWrite) {
      if (playersReady) {
        els.setupCard.classList.add('d-none');
        return;
      }

      const count = (state && state.player_count) || 4;
      const existing = (state && state.players) || [];
      els.playerInputs.innerHTML = '';
      const editable = canWrite && !playersLocked;
      for (let seat = 1; seat <= count; seat++) {
        const found = existing.find(function (p) { return p.seat === seat; });
        const row = document.createElement('div');
        row.className = 'seat-row';
        row.innerHTML =
          '<span class="seat-no">' + seat + '</span>' +
          '<input type="text" class="form-control player-name" data-seat="' + seat + '" maxlength="255" ' +
          'placeholder="Nama pemain ' + seat + '" value="' + (found ? String(found.nama).replace(/"/g, '&quot;') : '') + '" ' +
          (editable ? '' : 'readonly') + ' />';
        els.playerInputs.appendChild(row);
      }
      els.savePlayersBtn.classList.toggle('d-none', !editable);
      els.setupCard.classList.remove('d-none');
      const hint = els.setupCard.querySelector('p');
      if (hint) {
        hint.textContent = 'Isi 4 nama / julukan di meja ini.';
      }
    }

    function renderTotals() {
      const players = (state && state.players) || [];
      if (!players.length) {
        els.totalsCard.classList.add('d-none');
        return;
      }
      els.totalsCard.classList.remove('d-none');
      els.totalsGrid.innerHTML = players.map(function (p) {
        return '<div class="total-chip"><div class="name">' + escapeHtml(p.nama) + '</div><div class="pts">' + p.total + '</div></div>';
      }).join('');
    }

    function renderHandForm(canWrite, playersReady) {
      if (!canWrite || !playersReady) {
        els.handCard.classList.add('d-none');
        return;
      }
      els.handCard.classList.remove('d-none');
      const players = state.players || [];
      els.scoreInputs.innerHTML = '';
      players.forEach(function (p) {
        const row = document.createElement('div');
        row.className = 'score-player-row';
        row.innerHTML =
          '<span class="seat-no">' + p.seat + '</span>' +
          '<div>' +
            '<div class="score-player-name">' + escapeHtml(p.nama) + '</div>' +
            '<input type="number" inputmode="numeric" class="form-control hand-poin" data-seat="' + p.seat + '" ' +
            'placeholder="Isi Poin" value="" />' +
          '</div>';
        els.scoreInputs.appendChild(row);
      });
      winnerSeat = null;
      els.winnerToggle.innerHTML = players.map(function (p) {
        return '<button type="button" class="btn btn-outline-secondary btn-sm js-winner" data-seat="' + p.seat + '">' +
          escapeHtml(p.nama) + '</button>';
      }).join('');
      syncWinnerFromHandInputs();
    }

    function renderHistory(canWrite) {
      const hands = (state && state.hands) || [];
      const players = (state && state.players) || [];
      const seats = players.map(function (p) { return p.seat; });

      if (!players.length) {
        els.historyCard.classList.add('d-none');
        return;
      }

      if (document.activeElement && document.activeElement.classList.contains('history-poin')) {
        return;
      }

      els.historyCard.classList.remove('d-none');
      const activeHands = hands.filter(function (h) { return !h.voided; });
      els.voidLastBtn.classList.toggle('d-none', !(canWrite && activeHands.length));
      els.resetBtn.classList.toggle('d-none', !(canWrite && (hands.length || players.length)));
      els.handEmpty.classList.toggle('d-none', hands.length > 0);
      els.handTable.classList.toggle('d-none', hands.length === 0);
      if (els.handEditHint) {
        els.handEditHint.classList.toggle('d-none', !(canWrite && hands.length));
      }

      els.handTableHead.innerHTML = '<th>Ronde</th>' + players.map(function (p) {
        return '<th>' + escapeHtml(p.nama) + '</th>';
      }).join('');

      const orderedHands = hands.slice().sort(function (a, b) {
        return (a.hand_no || 0) - (b.hand_no || 0);
      });

      els.handTableBody.innerHTML = orderedHands.map(function (h) {
        const poinBySeat = {};
        (h.scores || []).forEach(function (s) {
          poinBySeat[s.seat] = s.poin;
        });
        const editable = canWrite && !h.voided;
        const cells = seats.map(function (seat) {
          const poin = poinBySeat[seat];
          const isWinner = h.winner_seat && Number(h.winner_seat) === Number(seat);
          const display = (poin === null || poin === undefined) ? '' : poin;
          if (editable) {
            return '<td class="' + (isWinner ? 'is-winner' : '') + '" data-seat="' + seat + '">' +
              '<input type="number" inputmode="numeric" class="form-control form-control-sm history-poin" ' +
              'data-hand-id="' + h.id + '" data-seat="' + seat + '" value="' + display + '" />' +
              '</td>';
          }
          return '<td class="' + (isWinner ? 'is-winner' : '') + '">' +
            (display === '' ? '-' : display) +
            '</td>';
        }).join('');
        const rondeLabel = h.voided ? (h.hand_no + ' (batal)') : String(h.hand_no);
        const signature = seats.map(function (seat) {
          const poin = poinBySeat[seat];
          return seat + ':' + (poin === null || poin === undefined ? '' : poin);
        }).join('|');
        return '<tr class="' + (h.voided ? 'voided' : '') + '" data-hand-id="' + h.id + '" data-signature="' + signature + '"><td>' +
          escapeHtml(rondeLabel) + '</td>' + cells + '</tr>';
      }).join('');
    }

    function escapeHtml(str) {
      return String(str == null ? '' : str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    }

    function parsePoinValue(raw) {
      const text = String(raw == null ? '' : raw).trim();
      if (text === '') return 0;
      const poin = parseInt(text, 10);
      return Number.isNaN(poin) ? null : poin;
    }

    function winnerSeatFromScoreMap(bySeat, preferredSeat) {
      const seats = Object.keys(bySeat);
      if (!seats.length) return null;
      let max = null;
      seats.forEach(function (seat) {
        const poin = bySeat[seat];
        if (max === null || poin > max) max = poin;
      });
      const leaders = seats.filter(function (seat) {
        return bySeat[seat] === max;
      }).map(function (seat) {
        return parseInt(seat, 10);
      });
      if (leaders.length === 1) return leaders[0];
      if (preferredSeat != null && leaders.indexOf(preferredSeat) !== -1) return preferredSeat;
      return null;
    }

    function paintWinnerButtons(selectedSeat) {
      Array.prototype.forEach.call(els.winnerToggle.querySelectorAll('.js-winner'), function (b) {
        const isOn = selectedSeat != null && String(b.getAttribute('data-seat')) === String(selectedSeat);
        b.classList.toggle('btn-success', isOn);
        b.classList.toggle('btn-outline-secondary', !isOn);
      });
    }

    function syncWinnerFromHandInputs() {
      const inputs = Array.prototype.slice.call(document.querySelectorAll('.hand-poin'));
      if (!inputs.length) return;
      const bySeat = {};
      let invalid = false;
      inputs.forEach(function (inp) {
        const poin = parsePoinValue(inp.value);
        if (poin === null) {
          invalid = true;
          return;
        }
        bySeat[inp.getAttribute('data-seat')] = poin;
      });
      if (invalid) return;
      winnerSeat = winnerSeatFromScoreMap(bySeat, winnerSeat);
      paintWinnerButtons(winnerSeat);
    }

    function collectRowScores(row) {
      const inputs = Array.prototype.slice.call(row.querySelectorAll('.history-poin'));
      const scores = [];
      for (let i = 0; i < inputs.length; i++) {
        const poin = parsePoinValue(inputs[i].value);
        if (poin === null) {
          return { error: 'Poin harus berupa angka.', input: inputs[i] };
        }
        scores.push({
          seat: parseInt(inputs[i].getAttribute('data-seat'), 10),
          poin: poin,
        });
      }
      return { scores: scores };
    }

    function applyRowWinner(row, selectedSeat) {
      Array.prototype.forEach.call(row.querySelectorAll('td[data-seat]'), function (td) {
        td.classList.toggle('is-winner', selectedSeat != null && String(td.getAttribute('data-seat')) === String(selectedSeat));
      });
    }

    function rowSignature(row) {
      return Array.prototype.map.call(row.querySelectorAll('.history-poin'), function (inp) {
        return inp.getAttribute('data-seat') + ':' + String(inp.value).trim();
      }).join('|');
    }

    function saveHistoryRow(row) {
      if (!row || row.getAttribute('data-saving') === '1') return;
      const handId = row.getAttribute('data-hand-id');
      if (!handId) return;
      const signature = rowSignature(row);
      if (signature === (row.getAttribute('data-signature') || '')) return;
      const collected = collectRowScores(row);
      if (collected.error) {
        showError(collected.error);
        if (collected.input) collected.input.focus();
        return;
      }
      const bySeat = {};
      collected.scores.forEach(function (s) { bySeat[s.seat] = s.poin; });
      const autoWinner = winnerSeatFromScoreMap(bySeat, null);
      applyRowWinner(row, autoWinner);
      row.setAttribute('data-saving', '1');
      const body = { scores: collected.scores };
      if (autoWinner != null) body.winner_seat = autoWinner;
      api(String(routes.updateHand).replace(/\/0$/, '/' + handId), { method: 'PUT', body: body })
        .then(function (r) {
          row.removeAttribute('data-saving');
          if (!r.ok) {
            showError(firstError(r.body));
            return;
          }
          state = r.body.data;
          row.setAttribute('data-signature', signature);
          showSuccess(r.body.message || 'Ronde diperbarui.');
          renderTotals();
          const updated = ((state && state.hands) || []).find(function (h) {
            return String(h.id) === String(handId);
          });
          if (updated) applyRowWinner(row, updated.winner_seat);
        })
        .catch(function () {
          row.removeAttribute('data-saving');
          showError('Jaringan bermasalah.');
        });
    }

    function openResetDialog() {
      els.resetPlayersCheck.checked = false;
      els.resetOverlay.classList.add('show');
      els.resetOverlay.setAttribute('aria-hidden', 'false');
    }

    function closeResetDialog() {
      els.resetOverlay.classList.remove('show');
      els.resetOverlay.setAttribute('aria-hidden', 'true');
    }

    function render() {
      if (!state) return;
      const canWrite = !!(state.session && state.session.can_write);
      const playersReady = !!(state.session && state.session.players_ready);
      const playersLocked = !!(state.session && state.session.players_locked);
      const rental = state.rental || {};
      els.meta.textContent = [rental.nama_toko, rental.nama_meja, rental.nama_customer]
        .filter(Boolean).join(' · ');
      els.readonly.classList.toggle('d-none', canWrite);
      renderSetup(playersReady, playersLocked, canWrite);
      renderTotals();
      renderHandForm(canWrite, playersReady);
      renderHistory(canWrite);
    }

    function load() {
      if (!token) {
        els.tokenMissing.classList.remove('d-none');
        els.app.classList.add('d-none');
        return;
      }
      api(routes.show, { method: 'GET' }).then(function (r) {
        if (!r.ok) {
          els.tokenMissing.classList.remove('d-none');
          els.app.classList.add('d-none');
          return;
        }
        els.tokenMissing.classList.add('d-none');
        els.app.classList.remove('d-none');
        state = r.body.data;
        clearAlerts();
        render();
      }).catch(function () {
        showError('Jaringan bermasalah.');
      });
    }

    els.savePlayersBtn.addEventListener('click', function () {
      clearAlerts();
      const inputs = Array.prototype.slice.call(document.querySelectorAll('.player-name'));
      const players = inputs.map(function (inp) {
        return { seat: parseInt(inp.getAttribute('data-seat'), 10), nama: inp.value.trim() };
      });
      els.savePlayersBtn.disabled = true;
      api(routes.players, { method: 'PUT', body: { players: players } })
        .then(function (r) {
          els.savePlayersBtn.disabled = false;
          if (!r.ok) {
            showError(firstError(r.body));
            return;
          }
          state = r.body.data;
          showSuccess(r.body.message || 'Tersimpan.');
          render();
        })
        .catch(function () {
          els.savePlayersBtn.disabled = false;
          showError('Jaringan bermasalah.');
        });
    });

    els.scoreInputs.addEventListener('input', function () {
      syncWinnerFromHandInputs();
    });

    els.winnerToggle.addEventListener('click', function (e) {
      const btn = e.target.closest('.js-winner');
      if (!btn) return;
      const seatAttr = btn.getAttribute('data-seat');
      const seat = seatAttr === '' || seatAttr == null ? null : parseInt(seatAttr, 10);
      winnerSeat = winnerSeat === seat ? null : seat;
      paintWinnerButtons(winnerSeat);
    });

    els.saveHandBtn.addEventListener('click', function () {
      clearAlerts();
      const inputs = Array.prototype.slice.call(document.querySelectorAll('.hand-poin'));
      const scores = [];
      for (let i = 0; i < inputs.length; i++) {
        const inp = inputs[i];
        const raw = inp.value.trim();
        let poin = 0;
        if (raw !== '') {
          poin = parseInt(raw, 10);
          if (Number.isNaN(poin)) {
            showError('Poin harus berupa angka.');
            inp.focus();
            return;
          }
        }
        scores.push({
          seat: parseInt(inp.getAttribute('data-seat'), 10),
          poin: poin,
        });
      }
      const bySeat = {};
      scores.forEach(function (s) { bySeat[s.seat] = s.poin; });
      winnerSeat = winnerSeatFromScoreMap(bySeat, winnerSeat);
      paintWinnerButtons(winnerSeat);
      const body = { scores: scores };
      if (winnerSeat != null) body.winner_seat = winnerSeat;
      els.saveHandBtn.disabled = true;
      api(routes.storeHand, { method: 'POST', body: body })
        .then(function (r) {
          els.saveHandBtn.disabled = false;
          if (!r.ok) {
            showError(firstError(r.body));
            return;
          }
          state = r.body.data;
          showSuccess(r.body.message || 'Ronde tersimpan.');
          render();
        })
        .catch(function () {
          els.saveHandBtn.disabled = false;
          showError('Jaringan bermasalah.');
        });
    });

    els.handTableBody.addEventListener('change', function (e) {
      const input = e.target.closest('.history-poin');
      if (!input) return;
      saveHistoryRow(input.closest('tr'));
    });

    els.handTableBody.addEventListener('focusout', function (e) {
      const input = e.target.closest('.history-poin');
      if (!input) return;
      const row = input.closest('tr');
      const next = e.relatedTarget;
      if (next && row && row.contains(next)) return;
      saveHistoryRow(row);
    });

    els.handTableBody.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter') return;
      const input = e.target.closest('.history-poin');
      if (!input) return;
      e.preventDefault();
      input.blur();
    });

    els.resetBtn.addEventListener('click', function () {
      clearAlerts();
      openResetDialog();
    });

    els.resetCancelBtn.addEventListener('click', closeResetDialog);
    els.resetOverlay.addEventListener('click', function (e) {
      if (e.target === els.resetOverlay) closeResetDialog();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && els.resetOverlay.classList.contains('show')) {
        closeResetDialog();
      }
    });

    els.resetConfirmBtn.addEventListener('click', function () {
      clearAlerts();
      els.resetConfirmBtn.disabled = true;
      api(routes.reset, {
        method: 'POST',
        body: { reset_players: !!els.resetPlayersCheck.checked },
      }).then(function (r) {
        els.resetConfirmBtn.disabled = false;
        if (!r.ok) {
          showError(firstError(r.body));
          return;
        }
        state = r.body.data;
        closeResetDialog();
        showSuccess(r.body.message || 'Skor direset.');
        render();
      }).catch(function () {
        els.resetConfirmBtn.disabled = false;
        showError('Jaringan bermasalah.');
      });
    });

    els.voidLastBtn.addEventListener('click', function () {
      if (!window.confirm('Batalkan ronde terakhir?')) return;
      clearAlerts();
      els.voidLastBtn.disabled = true;
      api(routes.voidLast, { method: 'POST', body: {} })
        .then(function (r) {
          els.voidLastBtn.disabled = false;
          if (!r.ok) {
            showError(firstError(r.body));
            return;
          }
          state = r.body.data;
          showSuccess(r.body.message || 'Ronde dibatalkan.');
          render();
        })
        .catch(function () {
          els.voidLastBtn.disabled = false;
          showError('Jaringan bermasalah.');
        });
    });

    load();
  })();
  </script>
</body>
</html>
