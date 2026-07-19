@extends('layouts.layout')

@section('content')
@php
  $fmtRp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
@endphp

<div class="app-content-header">
  <div class="container-fluid">
    <div class="row">
      <div class="col-sm-6">
        <h3 class="mb-0">Sewa Meja</h3>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
          <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
          <li class="breadcrumb-item active" aria-current="page">Sewa Meja</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="app-content">
  <div class="container-fluid">
    
    @php $totalMeja = $tokos->sum(fn ($t) => $t->meja->count()); @endphp

    @if ($totalMeja === 0)
      <div class="alert alert-secondary">Belum ada meja. Tambah data di menu <strong>Toko</strong>.</div>
    @else
      @foreach ($tokos as $toko)
        @if ($toko->meja->isNotEmpty())
          <h5 class="text-secondary mb-2">{{ $toko->nama }}</h5>
          <div class="row g-3 mb-4">
            @foreach ($toko->meja as $meja)
              @php
                $rental = $meja->activeRental;
                $occupied = $meja->status === 'rented' && $rental;
                $savedItemsCount = $occupied ? $rental->additionalItems->sum('qty') : 0;
                $savedItemsTotal = $occupied ? (float) $rental->additionalItems->sum('subtotal') : 0;
                $fbPaid = false;
                if ($occupied) {
                  $fbFlow = $rental->cashFlows->firstWhere('kategori_pendapatan', \App\Models\CashFlow::KATEGORI_ADDITIONAL_FB);
                  $fbPaid = $fbFlow && ! empty($fbFlow->metode_pembayaran);
                }
              @endphp
              <div class="col-6 col-md-3">
                <button
                  type="button"
                  class="btn w-100 h-100 p-0 border-0 text-start meja-card {{ $occupied ? 'meja-card--occupied' : 'meja-card--available' }}"
                  data-meja-id="{{ $meja->id }}"
                  data-meja-nama="{{ $meja->nama }}"
                  data-toko-nama="{{ $toko->nama }}"
                  data-toko-id="{{ $toko->id }}"
                  data-harga-non-member="{{ (float) $meja->harga }}"
                  data-harga-member="{{ (float) ($meja->harga_member ?? $meja->harga) }}"
                  @if ($occupied)
                    data-rental-id="{{ $rental->id }}"
                    data-start-epoch="{{ $rental->waktu_start->timestamp }}"
                    data-customer="{{ $rental->nama_customer }}"
                    data-tipe="{{ $rental->tipe_customer ?? 'non_member' }}"
                    data-items-count="{{ (int) $savedItemsCount }}"
                    data-items-total="{{ $savedItemsTotal }}"
                    data-items-paid="{{ $fbPaid ? '1' : '0' }}"
                  @endif
                >
                  <div class="card h-100 shadow-sm mb-0">
                    <div class="card-body p-3">
                      <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="fw-bold fs-5">{{ $meja->nama }}</span>
                        <span class="badge {{ $occupied ? 'text-bg-warning text-dark' : 'text-bg-success' }}">
                          {{ $occupied ? 'Disewa' : 'Tersedia' }}
                        </span>
                      </div>
                      @if ($occupied)
                        <div class="font-monospace fs-4 fw-semibold text-dark mb-1 meja-timer">00:00:00</div>
                        <div class="small text-truncate" title="{{ $rental->nama_customer }}">{{ $rental->nama_customer }}</div>
                        <div class="small text-secondary">{{ $rental->isMember() ? 'Member' : 'Non-Member' }}</div>
                        <div class="small text-secondary">Mulai: {{ $rental->waktu_start->format('H:i') }}</div>
                        @if ($savedItemsCount > 0)
                          <div class="small mt-1">
                            <span class="badge {{ $fbPaid ? 'text-bg-success' : 'text-bg-info text-dark' }}">
                              {{ (int) $savedItemsCount }} item
                              @if ($fbPaid) · lunas @endif
                            </span>
                          </div>
                        @endif
                      @else
                        <div class="small text-secondary mt-2">Non-Mbr: {{ $fmtRp($meja->harga) }}/jam</div>
                        <div class="small text-secondary">Member: {{ $fmtRp($meja->harga_member ?? $meja->harga) }}/jam</div>
                        <div class="small text-success mt-2">Ketuk untuk check-in</div>
                      @endif
                    </div>
                  </div>
                </button>
              </div>
            @endforeach
          </div>
        @endif
      @endforeach
    @endif
  </div>
</div>

{{-- Occupied action chooser --}}
<div class="modal fade" id="occupiedActionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Meja <span id="occupiedActionMejaLabel"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body d-grid gap-2">
        <p class="small text-secondary mb-1" id="occupiedActionCustomer"></p>
        <button type="button" class="btn btn-outline-primary" id="occupiedActionItemsBtn">
          <i class="bi bi-basket me-1"></i>Item tambahan
        </button>
        <button type="button" class="btn btn-warning" id="occupiedActionCheckoutBtn">
          <i class="bi bi-cash-coin me-1"></i>Checkout
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Mid-session items --}}
<div class="modal fade" id="itemsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Item tambahan — <span id="itemsMejaLabel">Meja</span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div id="itemsAlert" class="alert alert-danger d-none small"></div>
        <div id="itemsPaidBanner" class="alert alert-success d-none small py-2"></div>
        <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
          <h6 class="fw-semibold mb-0">Item tambahan &amp; diskon</h6>
          <button type="button" class="btn btn-sm btn-outline-primary" id="midTambahItemBtn">
            <i class="bi bi-plus-lg me-1"></i>Tambah item
          </button>
        </div>
        <div id="itemsEmpty" class="text-secondary small mb-2 d-none">Belum ada item di master.</div>
        <div class="table-responsive mb-2">
          <table class="table table-sm align-middle mb-0" id="midItemsTable">
            <thead class="table-light">
              <tr>
                <th>Item</th>
                <th class="text-end" style="width:100px">Nilai</th>
                <th style="width:90px">Qty</th>
                <th class="text-end" style="width:110px">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($additionalItems as $item)
                <tr data-item-id="{{ $item->id }}" data-item-harga="{{ (float) $item->harga }}" data-item-discount="{{ $item->is_discount ? '1' : '0' }}" data-item-toko="{{ (int) ($item->id_toko ?? 0) }}">
                  <td>
                    {{ $item->nama }}
                    @if ($item->is_discount)
                      <span class="badge text-bg-warning text-dark ms-1">Diskon</span>
                    @endif
                  </td>
                  <td class="text-end font-monospace small">
                    @if ($item->is_discount)
                      − {{ $fmtRp($item->harga) }}
                    @else
                      {{ $fmtRp($item->harga) }}
                    @endif
                  </td>
                  <td>
                    <input type="number" class="form-control form-control-sm mid-item-qty" min="0" max="999" value="0" data-item-id="{{ $item->id }}" />
                  </td>
                  <td class="text-end font-monospace small mid-item-line-total">Rp 0</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <nav id="midItemsPager" class="d-none mb-2" aria-label="Pagination item tambahan"></nav>
        <div class="border rounded p-3 bg-light mb-3">
          <div class="d-flex justify-content-between fw-semibold">
            <span>Total item</span>
            <span class="font-monospace" id="midItemsTotal">Rp 0</span>
          </div>
          <div class="d-flex justify-content-between small text-secondary mt-1">
            <span>Sudah dibayar</span>
            <span class="font-monospace" id="midItemsPaid">Rp 0</span>
          </div>
          <div class="d-flex justify-content-between small">
            <span>Sisa tagihan item</span>
            <span class="font-monospace" id="midItemsDue">Rp 0</span>
          </div>
        </div>

        <h6 class="fw-semibold mb-2">Bayar item sekarang (opsional)</h6>
        <div class="mb-2">
          <label for="items_jumlah_bayar" class="form-label">Jumlah bayar</label>
          <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="number" class="form-control" id="items_jumlah_bayar" min="0" step="1" />
          </div>
        </div>
        <div class="mb-2">
          <label for="items_metode" class="form-label">Metode pembayaran</label>
          <select class="form-select" id="items_metode">
            <option value="">— Pilih untuk bayar sekarang —</option>
            <option value="tunai">Tunai</option>
            <option value="transfer">Transfer bank</option>
            <option value="qris">QRIS / e-wallet</option>
            <option value="kartu">Kartu debit/kredit</option>
            <option value="lainnya">Lainnya</option>
          </select>
        </div>
        <div class="mb-0">
          <label for="items_bukti" class="form-label">Bukti bayar</label>
          <input type="file" class="form-control" id="items_bukti" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf" />
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-outline-primary" id="itemsSaveBtn">Simpan item</button>
        <button type="button" class="btn btn-primary" id="itemsSavePayBtn">Simpan &amp; bayar</button>
      </div>
    </div>
  </div>
</div>

{{-- Check-in --}}
<div class="modal fade" id="checkinModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Check-in — <span id="checkinMejaLabel">Meja</span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <form id="checkinForm" novalidate data-no-page-loader>
        <div class="modal-body">
          <div id="checkinAlert" class="alert alert-danger d-none"></div>
          <input type="hidden" id="checkin_id_meja" name="id_meja" />
          <div class="mb-3">
            <label class="form-label d-block">Tipe customer</label>
            <div class="btn-group w-100" role="group">
              <input type="radio" class="btn-check" name="tipe_customer" id="tipe_non_member" value="non_member" checked />
              <label class="btn btn-outline-primary" for="tipe_non_member">Non-Member</label>
              <input type="radio" class="btn-check" name="tipe_customer" id="tipe_member" value="member" />
              <label class="btn btn-outline-primary" for="tipe_member">Member</label>
            </div>
            <div class="form-text mt-2" id="checkinRateHint">Tarif: —</div>
          </div>
          @if ($rentalPromos->isNotEmpty())
            <div class="mb-3">
              <label for="checkin_id_promo" class="form-label">Promo / diskon</label>
              <select class="form-select" id="checkin_id_promo" name="id_promo">
                <option value="">— Tanpa promo (tarif normal) —</option>
                @foreach ($rentalPromos as $promo)
                  <option
                    value="{{ $promo->id }}"
                    data-toko-id="{{ (int) $promo->id_toko }}"
                    data-rate="{{ (float) $promo->promo_hourly_rate }}"
                    data-limit="{{ ($promo->promo_duration_limit !== null && (float) $promo->promo_duration_limit > 0) ? $promo->promo_duration_limit : '' }}"
                    data-jam-mulai="{{ $promo->jamMulaiFormatted() }}"
                    data-jam-selesai="{{ $promo->jamSelesaiFormatted() }}"
                  >
                    {{ $promo->nama }} — {{ $fmtRp($promo->promo_hourly_rate) }}/jam · {{ $promo->periodeFormatted() }} · {{ $promo->jamMulaiFormatted() }}–{{ $promo->jamSelesaiFormatted() }}
                  </option>
                @endforeach
              </select>
              <div class="form-text" id="checkinPromoHint"></div>
            </div>
          @endif
          <div class="mb-0">
            <label for="checkin_nama_customer" class="form-label">Nama customer</label>
            <input type="text" class="form-control" id="checkin_nama_customer" name="nama_customer" required maxlength="255" autocomplete="name" />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success">Mulai sewa</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Checkout --}}
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Checkout — <span id="checkoutMejaLabel">Meja</span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div id="checkoutSummary" class="border rounded p-3 bg-body-secondary small mb-3">Memuat…</div>

        <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
          <h6 class="fw-semibold mb-0">Item tambahan &amp; diskon</h6>
          <button type="button" class="btn btn-sm btn-outline-primary" id="checkoutTambahItemBtn">
            <i class="bi bi-plus-lg me-1"></i>Tambah item
          </button>
        </div>
        <div id="additionalItemsEmpty" class="text-secondary small mb-2 d-none">Belum ada item di master. Gunakan <strong>Tambah item</strong> atau menu Item tambahan.</div>
        <div class="table-responsive mb-2">
          <table class="table table-sm align-middle mb-0" id="additionalItemsTable">
            <thead class="table-light">
              <tr>
                <th>Item</th>
                <th class="text-end" style="width:100px">Nilai</th>
                <th style="width:90px">Qty</th>
                <th class="text-end" style="width:110px">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($additionalItems as $item)
                <tr data-item-id="{{ $item->id }}" data-item-harga="{{ (float) $item->harga }}" data-item-discount="{{ $item->is_discount ? '1' : '0' }}" data-item-toko="{{ (int) ($item->id_toko ?? 0) }}">
                  <td>
                    {{ $item->nama }}
                    @if ($item->is_discount)
                      <span class="badge text-bg-warning text-dark ms-1">Diskon</span>
                    @endif
                  </td>
                  <td class="text-end font-monospace small">
                    @if ($item->is_discount)
                      − {{ $fmtRp($item->harga) }}
                    @else
                      {{ $fmtRp($item->harga) }}
                    @endif
                  </td>
                  <td>
                    <input type="number" class="form-control form-control-sm additional-qty" min="0" max="999" value="0" data-item-id="{{ $item->id }}" />
                  </td>
                  <td class="text-end font-monospace small additional-line-total">Rp 0</td>
                </tr>
              @empty
              @endforelse
            </tbody>
          </table>
        </div>
        <nav id="additionalItemsPager" class="d-none mb-2" aria-label="Pagination item tambahan"></nav>

        <div class="border rounded p-3 bg-light mb-3">
          <div class="d-flex justify-content-between"><span>Biaya sewa meja</span><span class="font-monospace" id="checkoutSewaTotal">Rp 0</span></div>
          <div class="d-flex justify-content-between"><span>Item tambahan &amp; diskon</span><span class="font-monospace" id="checkoutAdditionalTotal">Rp 0</span></div>
          <div class="d-flex justify-content-between small text-secondary"><span>Item sudah dibayar</span><span class="font-monospace" id="checkoutAdditionalPaid">Rp 0</span></div>
          <hr class="my-2" />
          <div class="d-flex justify-content-between fw-bold fs-5"><span>Total tagihan</span><span class="font-monospace text-primary" id="checkoutGrandTotal">Rp 0</span></div>
          <div class="d-flex justify-content-between fw-semibold"><span>Sisa dibayar sekarang</span><span class="font-monospace" id="checkoutTotalDue">Rp 0</span></div>
        </div>

        <h6 class="fw-semibold mb-2">Pembayaran</h6>
        <div id="checkoutPaymentAlert" class="alert alert-danger d-none small"></div>

        <div class="mb-3">
          <label for="checkout_jumlah_bayar" class="form-label">Jumlah bayar</label>
          <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="number" class="form-control" id="checkout_jumlah_bayar" min="0" step="1" />
          </div>
          <div class="form-text">Opsional. Jika metode dipilih, default mengikuti sisa tagihan.</div>
        </div>

        <div class="mb-3">
          <label for="checkout_metode" class="form-label">Metode pembayaran</label>
          <select class="form-select" id="checkout_metode">
            <option value="">— Bayar nanti (isi di Data Sewa) —</option>
            <option value="tunai">Tunai</option>
            <option value="transfer">Transfer bank</option>
            <option value="qris">QRIS / e-wallet</option>
            <option value="kartu">Kartu debit/kredit</option>
            <option value="lainnya">Lainnya</option>
          </select>
          <div class="form-text">Jika belum dibayar sekarang, biarkan kosong. Bisa diisi belakangan di menu <strong>Data Sewa</strong>.</div>
        </div>

        <div class="mb-0">
          <label for="checkout_bukti" class="form-label">Bukti bayar <span id="checkout_bukti_required" class="text-danger">*</span></label>
          <input
            type="file"
            class="form-control"
            id="checkout_bukti"
            accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf"
          />
          <div class="form-text" id="checkout_bukti_help">Opsional. Unggah jika diperlukan arsip.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-danger me-auto" id="checkoutCancelBtn">Batalkan &amp; hapus sewa</button>
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="checkoutConfirmBtn" disabled>Checkout &amp; simpan pembayaran</button>
      </div>
    </div>
  </div>
</div>

{{-- Quick-add additional item (non-discount) --}}
<div class="modal fade" id="quickAddItemModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Tambah item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div id="quickAddItemAlert" class="alert alert-danger d-none small"></div>
        <div class="mb-3">
          <label for="quick_item_nama" class="form-label">Nama</label>
          <input type="text" class="form-control" id="quick_item_nama" maxlength="255" autocomplete="off" />
        </div>
        <div class="mb-0">
          <label for="quick_item_harga" class="form-label">Harga</label>
          <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="number" class="form-control" id="quick_item_harga" min="0" step="1" />
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="quickAddItemSaveBtn">Simpan</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  .meja-card { cursor: pointer; transition: transform 0.12s ease, box-shadow 0.12s ease; }
  .meja-card--available .card { border: 3px solid #198754; background: linear-gradient(180deg, #ecfdf3 0%, #f8fff9 100%); }
  .meja-card--occupied .card { border: 3px solid #fd7e14; background: linear-gradient(180deg, #fff4e8 0%, #fffaf5 100%); }
  .meja-card--available:hover .card { transform: translateY(-2px); box-shadow: 0 0.5rem 1rem rgba(25, 135, 84, 0.2); }
  .meja-card--occupied:hover .card { transform: translateY(-2px); box-shadow: 0 0.5rem 1rem rgba(253, 126, 20, 0.2); }
  .meja-card--available:focus-visible .card,
  .meja-card--occupied:focus-visible .card { box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.4); outline: none; }
  tr.item-toko-hidden,
  tr.item-page-hidden { display: none !important; }
</style>
@endpush

@push('scripts')
@php
  $masterItemsForJs = $additionalItems->map(function ($i) {
    return [
      'id' => $i->id,
      'id_toko' => (int) ($i->id_toko ?? 0),
      'nama' => $i->nama,
      'harga' => (float) $i->harga,
      'is_discount' => (bool) $i->is_discount,
    ];
  })->values();
  $canSeeAllToko = \App\Support\TokoScope::canSeeAll();
@endphp
<script>
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const masterItems = @json($masterItemsForJs);
  const userIdToko = @json(\App\Support\TokoScope::userIdToko());
  const canSeeAllTokoJs = @json($canSeeAllToko);
  const routes = {
    store: @json(route('rental.store')),
    checkoutPreview: (id) => @json(url('/sewa')) + '/' + id + '/checkout-preview',
    checkout: (id) => @json(url('/sewa')) + '/' + id + '/checkout',
    cancel: (id) => @json(url('/sewa')) + '/' + id + '/cancel',
    items: (id) => @json(url('/sewa')) + '/' + id + '/items',
    itemsPay: (id) => @json(url('/sewa')) + '/' + id + '/items/pay',
    quickAddItem: @json(route('additional-items.quick-store')),
  };

  const checkinModalEl = document.getElementById('checkinModal');
  const checkinModal = checkinModalEl ? new bootstrap.Modal(checkinModalEl) : null;
  const checkoutModalEl = document.getElementById('checkoutModal');
  const checkoutModal = checkoutModalEl ? new bootstrap.Modal(checkoutModalEl) : null;
  const occupiedActionModalEl = document.getElementById('occupiedActionModal');
  const occupiedActionModal = occupiedActionModalEl ? new bootstrap.Modal(occupiedActionModalEl) : null;
  const itemsModalEl = document.getElementById('itemsModal');
  const itemsModal = itemsModalEl ? new bootstrap.Modal(itemsModalEl) : null;

  let checkinMeja = null;
  let checkoutRentalId = null;
  let checkoutEndedAt = null;
  let checkoutGrandTotal = 0;
  let checkoutTotalDue = 0;
  let checkoutPrefillDone = false;
  let previewTimer = null;
  let occupiedBtn = null;
  let itemsRentalId = null;
  let itemsTokoId = 0;
  let checkoutTokoId = 0;
  let quickAddTokoId = 0;
  let midItemsTotal = 0;
  let midItemsPaid = 0;
  let midItemsDue = 0;

  const checkoutJumlahBayarEl = document.getElementById('checkout_jumlah_bayar');
  const checkoutMetodeEl = document.getElementById('checkout_metode');
  const checkoutBuktiEl = document.getElementById('checkout_bukti');
  const checkoutBuktiRequiredMark = document.getElementById('checkout_bukti_required');
  const checkoutBuktiHelpEl = document.getElementById('checkout_bukti_help');
  const checkoutPaymentAlert = document.getElementById('checkoutPaymentAlert');
  const checkoutCancelBtn = document.getElementById('checkoutCancelBtn');
  const itemsMetodeEl = document.getElementById('items_metode');
  const itemsJumlahBayarEl = document.getElementById('items_jumlah_bayar');
  const itemsBuktiEl = document.getElementById('items_bukti');
  const itemsAlert = document.getElementById('itemsAlert');
  const itemsPaidBanner = document.getElementById('itemsPaidBanner');

  function pad2(n) { return String(n).padStart(2, '0'); }
  function formatHMS(totalSeconds) {
    const s = Math.max(0, Math.floor(totalSeconds));
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    const sec = s % 60;
    return pad2(h) + ':' + pad2(m) + ':' + pad2(sec);
  }
  function fmtRp(n) {
    const val = Number(n || 0);
    if (val < 0) {
      return '− Rp ' + Math.abs(val).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    }
    return 'Rp ' + val.toLocaleString('id-ID', { maximumFractionDigits: 0 });
  }
  function additionalLineSubtotal(row, qty) {
    const harga = parseFloat(row?.getAttribute('data-item-harga') || '0');
    const isDiscount = row?.getAttribute('data-item-discount') === '1';
    const subtotal = harga * qty;
    return isDiscount ? -subtotal : subtotal;
  }
  function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s || '';
    return d.innerHTML;
  }

  function tickTimers() {
    const nowSec = Math.floor(Date.now() / 1000);
    document.querySelectorAll('.meja-card--occupied[data-start-epoch]').forEach(function (btn) {
      const start = parseInt(btn.getAttribute('data-start-epoch'), 10);
      const el = btn.querySelector('.meja-timer');
      if (el && !Number.isNaN(start)) el.textContent = formatHMS(nowSec - start);
    });
  }
  tickTimers();
  setInterval(tickTimers, 1000);

  function updateCheckinRateHint() {
    const hint = document.getElementById('checkinRateHint');
    if (!hint || !checkinMeja) return;
    const member = document.getElementById('tipe_member')?.checked;
    const rate = member ? checkinMeja.hargaMember : checkinMeja.hargaNonMember;
    hint.textContent = 'Tarif normal: ' + fmtRp(rate) + ' / jam (' + (member ? 'Member' : 'Non-Member') + ')';
    updateCheckinPromoOptions();
  }

  function updateCheckinPromoOptions() {
    const select = document.getElementById('checkin_id_promo');
    const promoHint = document.getElementById('checkinPromoHint');
    if (!select || !checkinMeja) return;
    const tokoId = parseInt(checkinMeja.tokoId || '0', 10) || 0;
    let visibleCount = 0;
    Array.from(select.options).forEach(function (opt, idx) {
      if (idx === 0) {
        opt.hidden = false;
        return;
      }
      const optToko = parseInt(opt.getAttribute('data-toko-id') || '0', 10) || 0;
      const show = !tokoId || optToko === tokoId;
      opt.hidden = !show;
      if (!show && opt.selected) {
        select.value = '';
      }
      if (show) visibleCount++;
    });
    const sel = select.options[select.selectedIndex];
    if (promoHint) {
      if (sel && sel.value) {
        const rate = parseFloat(sel.getAttribute('data-rate') || '0');
        const limitRaw = sel.getAttribute('data-limit');
        const limit = limitRaw === '' || limitRaw === null ? 0 : parseFloat(limitRaw);
        const jamMulai = sel.getAttribute('data-jam-mulai') || '';
        const jamSelesai = sel.getAttribute('data-jam-selesai') || '';
        let hint = 'Promo: ' + fmtRp(rate) + '/jam · jam ' + jamMulai + '–' + jamSelesai;
        if (!limit || limit <= 0 || Number.isNaN(limit)) {
          hint += ' (tanpa batas durasi; tarif promo dalam jam promo saat checkout).';
        } else {
          hint += ' (maks. ' + limit + ' jam ditagihkan dengan tarif promo).';
        }
        promoHint.textContent = hint;
      } else {
        promoHint.textContent = visibleCount > 0
          ? 'Opsional. Tarif promo berlaku untuk menit sewa dalam jam promo saat checkout.'
          : '';
      }
    }
  }

  document.getElementById('checkin_id_promo')?.addEventListener('change', updateCheckinPromoOptions);

  document.querySelectorAll('input[name="tipe_customer"]').forEach(function (inp) {
    inp.addEventListener('change', updateCheckinRateHint);
  });

  document.querySelectorAll('.meja-card--available').forEach(function (btn) {
    btn.addEventListener('click', function () { openCheckin(btn); });
  });
  document.querySelectorAll('.meja-card--occupied').forEach(function (btn) {
    btn.addEventListener('click', function () { openOccupiedActions(btn); });
  });

  function openOccupiedActions(btn) {
    occupiedBtn = btn;
    document.getElementById('occupiedActionMejaLabel').textContent = btn.getAttribute('data-meja-nama') || '';
    document.getElementById('occupiedActionCustomer').textContent = btn.getAttribute('data-customer') || '';
    occupiedActionModal?.show();
  }

  document.getElementById('occupiedActionItemsBtn')?.addEventListener('click', function () {
    if (!occupiedBtn) return;
    occupiedActionModal?.hide();
    openItemsModal(occupiedBtn);
  });

  document.getElementById('occupiedActionCheckoutBtn')?.addEventListener('click', function () {
    if (!occupiedBtn) return;
    occupiedActionModal?.hide();
    openCheckout(occupiedBtn);
  });

  function filterItemRows(tableSelector, tokoId) {
    const canSeeAll = @json($canSeeAllToko);
    document.querySelectorAll(tableSelector + ' tbody tr[data-item-id]').forEach(function (row) {
      if (canSeeAll && tokoId) {
        const itemToko = parseInt(row.getAttribute('data-item-toko') || '0', 10) || 0;
        row.classList.toggle('item-toko-hidden', itemToko !== tokoId);
      } else {
        row.classList.remove('item-toko-hidden');
      }
    });
    const tableEl = document.querySelector(tableSelector);
    if (tableEl) {
      tableEl.dataset.page = '1';
      paginateItemTable(tableEl);
    }
  }

  const ITEMS_PER_PAGE = 10;

  function paginateItemTable(tableEl, page) {
    if (!tableEl) return;
    const pagerId = tableEl.id === 'midItemsTable'
      ? 'midItemsPager'
      : (tableEl.id === 'additionalItemsTable' ? 'additionalItemsPager' : null);
    const pagerEl = pagerId ? document.getElementById(pagerId) : null;
    const allRows = Array.from(tableEl.querySelectorAll('tbody tr[data-item-id]'));
    const eligible = allRows.filter(function (row) {
      return !row.classList.contains('item-toko-hidden');
    });

    const total = eligible.length;
    const totalPages = Math.max(1, Math.ceil(total / ITEMS_PER_PAGE));
    let current = page != null ? page : (parseInt(tableEl.dataset.page || '1', 10) || 1);
    current = Math.min(Math.max(1, current), totalPages);
    tableEl.dataset.page = String(current);

    allRows.forEach(function (row) {
      row.classList.add('item-page-hidden');
    });
    eligible.forEach(function (row, idx) {
      const onPage = idx >= (current - 1) * ITEMS_PER_PAGE && idx < current * ITEMS_PER_PAGE;
      row.classList.toggle('item-page-hidden', !onPage);
    });

    if (!pagerEl) return;
    if (total <= ITEMS_PER_PAGE) {
      pagerEl.classList.add('d-none');
      pagerEl.innerHTML = '';
      return;
    }

    pagerEl.classList.remove('d-none');
    pagerEl.innerHTML =
      '<div class="d-flex flex-wrap align-items-center justify-content-between gap-2">' +
        '<span class="small text-secondary">' +
          'Menampilkan ' + ((current - 1) * ITEMS_PER_PAGE + 1) + '–' + Math.min(current * ITEMS_PER_PAGE, total) +
          ' dari ' + total + ' item' +
        '</span>' +
        '<div class="btn-group btn-group-sm" role="group">' +
          '<button type="button" class="btn btn-outline-secondary item-page-prev"' + (current <= 1 ? ' disabled' : '') + '>Sebelumnya</button>' +
          '<button type="button" class="btn btn-outline-secondary disabled">' + current + ' / ' + totalPages + '</button>' +
          '<button type="button" class="btn btn-outline-secondary item-page-next"' + (current >= totalPages ? ' disabled' : '') + '>Berikutnya</button>' +
        '</div>' +
      '</div>';

    pagerEl.querySelector('.item-page-prev')?.addEventListener('click', function () {
      paginateItemTable(tableEl, current - 1);
    });
    pagerEl.querySelector('.item-page-next')?.addEventListener('click', function () {
      paginateItemTable(tableEl, current + 1);
    });
  }

  function resetQtyInTable(qtySelector, lineSelector) {
    document.querySelectorAll(qtySelector).forEach(function (inp) {
      inp.value = '0';
      const row = inp.closest('tr');
      const cell = row?.querySelector(lineSelector);
      if (cell) cell.textContent = fmtRp(0);
    });
  }

  function collectQtyFromTable(qtySelector) {
    const items = [];
    document.querySelectorAll(qtySelector).forEach(function (inp) {
      const row = inp.closest('tr');
      if (row?.classList.contains('item-toko-hidden')) return;
      const qty = parseInt(inp.value, 10) || 0;
      if (qty > 0) items.push({ id: parseInt(inp.getAttribute('data-item-id'), 10), qty: qty });
    });
    return items;
  }

  function applyQtyToTable(qtySelector, lineSelector, lines) {
    resetQtyInTable(qtySelector, lineSelector);
    (lines || []).forEach(function (line) {
      const inp = document.querySelector(qtySelector + '[data-item-id="' + line.id + '"]');
      if (!inp) return;
      inp.value = String(line.qty);
      const row = inp.closest('tr');
      const cell = row?.querySelector(lineSelector);
      if (cell) cell.textContent = fmtRp(line.subtotal != null ? line.subtotal : additionalLineSubtotal(row, line.qty));
    });
  }

  function updateMidItemsLocalTotal() {
    let total = 0;
    document.querySelectorAll('.mid-item-qty').forEach(function (inp) {
      const row = inp.closest('tr');
      if (row?.classList.contains('item-toko-hidden')) return;
      const qty = parseInt(inp.value, 10) || 0;
      total += additionalLineSubtotal(row, qty);
    });
    midItemsTotal = total;
    midItemsDue = Math.max(0, midItemsTotal - midItemsPaid);
    document.getElementById('midItemsTotal').textContent = fmtRp(midItemsTotal);
    document.getElementById('midItemsPaid').textContent = fmtRp(midItemsPaid);
    document.getElementById('midItemsDue').textContent = fmtRp(midItemsDue);
    if (itemsJumlahBayarEl && (itemsJumlahBayarEl.dataset.auto === '1' || itemsJumlahBayarEl.value === '')) {
      itemsJumlahBayarEl.value = String(Math.round(midItemsTotal));
      itemsJumlahBayarEl.dataset.auto = '1';
    }
  }

  function setItemsPaymentState(payload) {
    midItemsPaid = Number(payload.additional_paid || 0);
    midItemsTotal = Number(payload.additional_total || 0);
    midItemsDue = Number(payload.additional_due || 0);
    document.getElementById('midItemsTotal').textContent = fmtRp(midItemsTotal);
    document.getElementById('midItemsPaid').textContent = fmtRp(midItemsPaid);
    document.getElementById('midItemsDue').textContent = fmtRp(midItemsDue);
    if (itemsPaidBanner) {
      if (payload.is_fully_paid && midItemsTotal !== 0) {
        itemsPaidBanner.textContent = 'Item tambahan sudah lunas' + (payload.metode_pembayaran ? ' (' + payload.metode_pembayaran + ').' : '.');
        itemsPaidBanner.classList.remove('d-none');
      } else if (midItemsPaid > 0) {
        itemsPaidBanner.textContent = 'Sebagian sudah dibayar: ' + fmtRp(midItemsPaid) + '. Sisa: ' + fmtRp(midItemsDue) + '.';
        itemsPaidBanner.classList.remove('d-none');
      } else {
        itemsPaidBanner.classList.add('d-none');
      }
    }
  }

  function openItemsModal(btn) {
    itemsRentalId = btn.getAttribute('data-rental-id');
    itemsTokoId = parseInt(btn.getAttribute('data-toko-id') || '0', 10) || 0;
    quickAddTokoId = itemsTokoId;
    document.getElementById('itemsMejaLabel').textContent = btn.getAttribute('data-meja-nama') || 'Meja';
    if (itemsAlert) { itemsAlert.classList.add('d-none'); itemsAlert.textContent = ''; }
    if (itemsMetodeEl) itemsMetodeEl.value = '';
    if (itemsBuktiEl) itemsBuktiEl.value = '';
    if (itemsJumlahBayarEl) { itemsJumlahBayarEl.value = ''; itemsJumlahBayarEl.dataset.auto = '1'; }
    filterItemRows('#midItemsTable', itemsTokoId);
    resetQtyInTable('.mid-item-qty', '.mid-item-line-total');
    document.getElementById('itemsEmpty')?.classList.toggle('d-none', masterItems.length > 0);
    itemsModal?.show();

    fetch(routes.items(itemsRentalId), { headers: { Accept: 'application/json' } })
      .then(function (res) { return res.json().then(function (body) { return { ok: res.ok, body: body }; }); })
      .then(function (r) {
        if (!r.ok) return;
        applyQtyToTable('.mid-item-qty', '.mid-item-line-total', r.body.items || []);
        setItemsPaymentState(r.body);
        if (itemsJumlahBayarEl) {
          itemsJumlahBayarEl.value = String(Math.round(Number(r.body.additional_total || 0)));
          itemsJumlahBayarEl.dataset.auto = '1';
        }
      })
      .catch(function () {});
  }

  document.getElementById('midItemsTable')?.addEventListener('input', function (e) {
    const inp = e.target.closest('.mid-item-qty');
    if (!inp) return;
    const row = inp.closest('tr');
    const qty = parseInt(inp.value, 10) || 0;
    const cell = row?.querySelector('.mid-item-line-total');
    if (cell) cell.textContent = fmtRp(additionalLineSubtotal(row, qty));
    updateMidItemsLocalTotal();
  });

  itemsJumlahBayarEl?.addEventListener('input', function () {
    itemsJumlahBayarEl.dataset.auto = '0';
  });

  function showItemsAlert(msg) {
    if (!itemsAlert) return;
    itemsAlert.textContent = msg || 'Terjadi kesalahan.';
    itemsAlert.classList.remove('d-none');
  }

  document.getElementById('itemsSaveBtn')?.addEventListener('click', function () {
    if (!itemsRentalId) return;
    const btn = this;
    btn.disabled = true;
    fetch(routes.items(itemsRentalId), {
      method: 'PUT',
      headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify({ additional_items: collectQtyFromTable('.mid-item-qty') }),
    })
      .then(function (res) { return res.json().then(function (body) { return { ok: res.ok, status: res.status, body: body }; }); })
      .then(function (r) {
        btn.disabled = false;
        if (!r.ok) {
          const msg = r.status === 422 && r.body?.errors
            ? (Object.values(r.body.errors)[0]?.[0] || 'Validasi gagal.')
            : (r.body?.message || 'Gagal menyimpan item.');
          showItemsAlert(msg);
          return;
        }
        setItemsPaymentState(r.body);
        AppToast.show(r.body?.message || 'Item disimpan.', 'success');
      })
      .catch(function () {
        btn.disabled = false;
        showItemsAlert('Jaringan bermasalah.');
      });
  });

  document.getElementById('itemsSavePayBtn')?.addEventListener('click', function () {
    if (!itemsRentalId) return;
    const metode = itemsMetodeEl?.value || '';
    const jumlah = parseFloat(itemsJumlahBayarEl?.value ?? '');
    if (!metode) {
      showItemsAlert('Pilih metode pembayaran untuk bayar item sekarang.');
      return;
    }
    if (!Number.isFinite(jumlah) || jumlah < 0) {
      showItemsAlert('Jumlah bayar wajib diisi.');
      return;
    }
    const btn = this;
    btn.disabled = true;
    const fd = new FormData();
    fd.append('additional_items', JSON.stringify(collectQtyFromTable('.mid-item-qty')));
    fd.append('metode_pembayaran', metode);
    fd.append('jumlah_bayar', String(jumlah));
    if (itemsBuktiEl?.files?.length) fd.append('bukti', itemsBuktiEl.files[0]);

    fetch(routes.itemsPay(itemsRentalId), {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
      body: fd,
    })
      .then(function (res) { return res.json().then(function (body) { return { ok: res.ok, status: res.status, body: body }; }); })
      .then(function (r) {
        btn.disabled = false;
        if (!r.ok) {
          const msg = r.status === 422 && r.body?.errors
            ? (Object.values(r.body.errors)[0]?.[0] || 'Validasi gagal.')
            : (r.body?.message || 'Gagal membayar item.');
          showItemsAlert(msg);
          return;
        }
        applyQtyToTable('.mid-item-qty', '.mid-item-line-total', r.body.items || []);
        setItemsPaymentState(r.body);
        AppToast.show(r.body?.message || 'Pembayaran item tersimpan.', 'success');
        setTimeout(function () { window.location.reload(); }, 900);
      })
      .catch(function () {
        btn.disabled = false;
        showItemsAlert('Jaringan bermasalah.');
      });
  });

  function syncCheckoutJumlahDefault() {
    if (checkoutJumlahBayarEl && (checkoutJumlahBayarEl.value === '' || checkoutJumlahBayarEl.dataset.auto === '1')) {
      checkoutJumlahBayarEl.value = String(Math.round(checkoutTotalDue));
      checkoutJumlahBayarEl.dataset.auto = '1';
    }
  }

  function openCheckin(btn) {
    checkinMeja = {
      id: btn.getAttribute('data-meja-id'),
      nama: btn.getAttribute('data-meja-nama'),
      tokoId: btn.getAttribute('data-toko-id'),
      hargaNonMember: parseFloat(btn.getAttribute('data-harga-non-member')) || 0,
      hargaMember: parseFloat(btn.getAttribute('data-harga-member')) || 0,
    };
    document.getElementById('checkinMejaLabel').textContent = checkinMeja.nama;
    document.getElementById('checkin_id_meja').value = checkinMeja.id;
    document.getElementById('checkin_nama_customer').value = '';
    document.getElementById('tipe_non_member').checked = true;
    const promoSel = document.getElementById('checkin_id_promo');
    if (promoSel) promoSel.value = '';
    document.getElementById('checkinAlert')?.classList.add('d-none');
    updateCheckinRateHint();
    checkinModal?.show();
  }

  function hideCheckoutPaymentAlert() {
    if (!checkoutPaymentAlert) return;
    checkoutPaymentAlert.classList.add('d-none');
    checkoutPaymentAlert.textContent = '';
  }

  function showCheckoutPaymentAlert(msg) {
    if (!checkoutPaymentAlert) return;
    checkoutPaymentAlert.textContent = msg || 'Terjadi kesalahan.';
    checkoutPaymentAlert.classList.remove('d-none');
  }

  function syncCheckoutBuktiField() {
    const metode = checkoutMetodeEl ? checkoutMetodeEl.value : '';
    const isEmpty = metode === '';
    const isTunai = checkoutMetodeEl && checkoutMetodeEl.value === 'tunai';
    if (checkoutBuktiRequiredMark) {
      checkoutBuktiRequiredMark.classList.add('d-none');
    }
    if (checkoutBuktiHelpEl) {
      checkoutBuktiHelpEl.textContent = isEmpty
        ? 'Kosongkan jika pembayaran akan dilengkapi nanti di Data Sewa.'
        : (isTunai
          ? 'Opsional untuk tunai. Wajib untuk transfer, QRIS, kartu, dan lainnya.'
          : 'Opsional. JPG, PNG, WEBP, atau PDF. Maks. 5 MB.');
    }
  }

  checkoutMetodeEl?.addEventListener('change', syncCheckoutBuktiField);
  syncCheckoutBuktiField();

  function resetCheckoutPaymentFields() {
    if (checkoutMetodeEl) checkoutMetodeEl.value = '';
    if (checkoutJumlahBayarEl) checkoutJumlahBayarEl.value = '';
    if (checkoutBuktiEl) checkoutBuktiEl.value = '';
    syncCheckoutBuktiField();
    hideCheckoutPaymentAlert();
  }

  function openCheckout(btn) {
    checkoutRentalId = btn.getAttribute('data-rental-id');
    checkoutEndedAt = Math.floor(Date.now() / 1000);
    checkoutGrandTotal = 0;
    checkoutTotalDue = 0;
    checkoutPrefillDone = false;
    document.getElementById('checkoutMejaLabel').textContent = btn.getAttribute('data-meja-nama') || 'Meja';
    const tokoId = parseInt(btn.getAttribute('data-toko-id') || '0', 10) || 0;
    checkoutTokoId = tokoId;
    quickAddTokoId = tokoId;
    filterItemRows('#additionalItemsTable', tokoId);
    resetAdditionalQty();
    resetCheckoutPaymentFields();
    document.getElementById('checkoutSummary').innerHTML = '<p class="mb-0 text-secondary">Memuat…</p>';
    document.getElementById('checkoutConfirmBtn').disabled = true;
    document.getElementById('additionalItemsEmpty')?.classList.toggle('d-none', masterItems.length > 0);
    checkoutModal?.show();

    fetch(routes.items(checkoutRentalId), { headers: { Accept: 'application/json' } })
      .then(function (res) { return res.json().then(function (body) { return { ok: res.ok, body: body }; }); })
      .then(function (r) {
        if (r.ok) {
          applyQtyToTable('.additional-qty', '.additional-line-total', r.body.items || []);
        }
        checkoutPrefillDone = true;
        refreshCheckoutPreview();
      })
      .catch(function () {
        checkoutPrefillDone = true;
        refreshCheckoutPreview();
      });
  }

  function resetAdditionalQty() {
    document.querySelectorAll('.additional-qty').forEach(function (inp) {
      inp.value = '0';
      const row = inp.closest('tr');
      const cell = row?.querySelector('.additional-line-total');
      if (cell) cell.textContent = fmtRp(0);
    });
  }

  function collectAdditionalItems() {
    return collectQtyFromTable('.additional-qty');
  }

  function refreshCheckoutPreview() {
    if (!checkoutRentalId) return;
    if (previewTimer) clearTimeout(previewTimer);

    const additional_items = collectAdditionalItems();

    fetch(routes.checkoutPreview(checkoutRentalId), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrf,
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ ended_at: checkoutEndedAt, additional_items: additional_items }),
    })
      .then(function (res) { return res.json().then(function (body) { return { ok: res.ok, body: body }; }); })
      .then(function (r) {
        if (!r.ok) {
          document.getElementById('checkoutSummary').innerHTML = '<p class="text-danger mb-0">Gagal memuat.</p>';
          return;
        }
        const d = r.body;
        document.getElementById('checkoutSummary').innerHTML =
          '<p class="mb-1"><strong>' + escapeHtml(d.nama_customer) + '</strong> · ' + escapeHtml(d.tipe_customer_label) + '</p>' +
          '<p class="mb-2 font-monospace fs-5 fw-semibold">' + escapeHtml(d.durasi_hms) + '</p>' +
          (d.breakdown_html || '');
        checkoutGrandTotal = Number(d.total_harga) || 0;
        checkoutTotalDue = Number(d.total_due != null ? d.total_due : checkoutGrandTotal) || 0;
        document.getElementById('checkoutSewaTotal').textContent = fmtRp(d.total_harga_sewa);
        document.getElementById('checkoutAdditionalTotal').textContent = fmtRp(d.total_harga_additional);
        document.getElementById('checkoutAdditionalPaid').textContent = fmtRp(d.additional_paid || 0);
        document.getElementById('checkoutGrandTotal').textContent = fmtRp(checkoutGrandTotal);
        document.getElementById('checkoutTotalDue').textContent = fmtRp(checkoutTotalDue);
        syncCheckoutJumlahDefault();
        document.getElementById('checkoutConfirmBtn').disabled = false;

        if (!checkoutPrefillDone) {
          (d.additional_lines || []).forEach(function (line) {
            const inp = document.querySelector('.additional-qty[data-item-id="' + line.id + '"]');
            if (inp) {
              inp.value = String(line.qty);
              const row = inp.closest('tr');
              const cell = row?.querySelector('.additional-line-total');
              if (cell) cell.textContent = fmtRp(line.subtotal);
            }
          });
        }
      })
      .catch(function () {
        document.getElementById('checkoutSummary').innerHTML = '<p class="text-danger mb-0">Jaringan bermasalah.</p>';
      });
  }

  document.getElementById('additionalItemsTable')?.addEventListener('input', function (e) {
    const inp = e.target.closest('.additional-qty');
    if (!inp) return;
    const row = inp.closest('tr');
    const qty = parseInt(inp.value, 10) || 0;
    const cell = row?.querySelector('.additional-line-total');
    if (cell) cell.textContent = fmtRp(additionalLineSubtotal(row, qty));
    clearTimeout(previewTimer);
    previewTimer = setTimeout(refreshCheckoutPreview, 400);
  });

  document.getElementById('checkinForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const tipe = document.querySelector('input[name="tipe_customer"]:checked')?.value || 'non_member';
    const payload = {
      id_meja: document.getElementById('checkin_id_meja').value,
      nama_customer: document.getElementById('checkin_nama_customer').value.trim(),
      tipe_customer: tipe,
    };
    const idPromo = document.getElementById('checkin_id_promo')?.value;
    if (idPromo) payload.id_promo = parseInt(idPromo, 10);
    fetch(routes.store, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
      .then(function (res) { return res.json().then(function (body) { return { ok: res.ok, status: res.status, body: body }; }); })
      .then(function (r) {
        if (r.ok) {
          AppToast.saveForReload(r.body?.message || 'Check-in berhasil.');
          window.location.reload();
          return;
        }
        const msg = r.status === 422 && r.body?.errors
          ? (Object.values(r.body.errors)[0]?.[0] || 'Validasi gagal.')
          : (r.body?.message || 'Gagal.');
        const alert = document.getElementById('checkinAlert');
        if (alert) { alert.textContent = msg; alert.classList.remove('d-none'); }
        AppToast.show(msg, 'danger');
      })
      .catch(function () { AppToast.show('Jaringan bermasalah.', 'danger'); });
  });

  checkoutJumlahBayarEl?.addEventListener('input', function () {
    checkoutJumlahBayarEl.dataset.auto = '0';
  });

  function confirmProceedWithoutBukti(onConfirm) {
    const message = 'Anda belum mengunggah bukti pembayaran. Lanjutkan tanpa bukti? Bukti dapat dilengkapi nanti di menu Data Sewa.';
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: 'Bukti belum diunggah',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, lanjutkan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#0d6efd',
      }).then(function (result) {
        if (result.isConfirmed) onConfirm();
      });
      return;
    }
    if (window.confirm(message)) onConfirm();
  }

  document.getElementById('checkoutConfirmBtn')?.addEventListener('click', function () {
    if (!checkoutRentalId) return;
    hideCheckoutPaymentAlert();

    const metode = checkoutMetodeEl?.value || '';
    const jumlahBayarRaw = checkoutJumlahBayarEl?.value ?? '';
    const jumlahBayar = jumlahBayarRaw === '' ? NaN : parseFloat(jumlahBayarRaw);
    const hasBukti = checkoutBuktiEl?.files?.length > 0;

    if (metode) {
      if (!Number.isFinite(jumlahBayar) || jumlahBayar < 0) {
        showCheckoutPaymentAlert('Jumlah bayar wajib diisi (min. 0) jika metode pembayaran dipilih.');
        checkoutJumlahBayarEl?.focus();
        return;
      }
    }

    const btn = this;

    function doCheckout() {
      btn.disabled = true;

      const fd = new FormData();
      fd.append('ended_at', String(checkoutEndedAt));
      fd.append('additional_items', JSON.stringify(collectAdditionalItems()));
      fd.append('payment_scope', 'all');
      if (metode) {
        fd.append('metode_pembayaran', metode);
        fd.append('jumlah_bayar', String(jumlahBayar));
        if (hasBukti) {
          fd.append('bukti', checkoutBuktiEl.files[0]);
        }
      }

      fetch(routes.checkout(checkoutRentalId), {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
        body: fd,
      })
        .then(function (res) { return res.json().then(function (body) { return { ok: res.ok, status: res.status, body: body }; }); })
        .then(function (r) {
          if (r.ok) {
            checkoutModal?.hide();
            const msg = r.body?.message || 'Checkout selesai.';
            if (r.body?.invoice_url) {
              window.open(r.body.invoice_url, '_blank', 'noopener,noreferrer');
            }
            AppToast.saveForReload(msg);
            window.location.reload();
            return;
          }
          btn.disabled = false;
          let errMsg = r.body?.message || 'Checkout gagal.';
          if (r.status === 422 && r.body?.errors) {
            const first = Object.values(r.body.errors)[0];
            errMsg = Array.isArray(first) ? first[0] : String(first);
          }
          showCheckoutPaymentAlert(errMsg);
          AppToast.show(errMsg, 'danger');
        })
        .catch(function () {
          btn.disabled = false;
          showCheckoutPaymentAlert('Jaringan bermasalah.');
          AppToast.show('Jaringan bermasalah.', 'danger');
        });
    }

    if (!hasBukti) {
      confirmProceedWithoutBukti(doCheckout);
      return;
    }

    doCheckout();
  });

  checkoutCancelBtn?.addEventListener('click', function () {
    if (!checkoutRentalId) return;
    const btn = this;

    function doCancel() {
      btn.disabled = true;
      fetch(routes.cancel(checkoutRentalId), {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
      })
        .then(function (res) { return res.json().then(function (body) { return { ok: res.ok, body: body }; }); })
        .then(function (r) {
          if (r.ok) {
            checkoutModal?.hide();
            AppToast.saveForReload(r.body?.message || 'Sewa dibatalkan.');
            window.location.reload();
            return;
          }
          btn.disabled = false;
          const msg = r.body?.message || 'Gagal membatalkan sewa.';
          showCheckoutPaymentAlert(msg);
          AppToast.show(msg, 'danger');
        })
        .catch(function () {
          btn.disabled = false;
          showCheckoutPaymentAlert('Jaringan bermasalah.');
          AppToast.show('Jaringan bermasalah.', 'danger');
        });
    }

    const message = 'Batalkan dan hapus sewa yang sedang berjalan? Aksi ini tidak bisa dibatalkan.';
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: 'Batalkan sewa?',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, batalkan',
        cancelButtonText: 'Kembali',
        confirmButtonColor: '#dc3545',
      }).then(function (result) {
        if (result.isConfirmed) doCancel();
      });
      return;
    }

    if (window.confirm(message)) doCancel();
  });

  checkoutModalEl?.addEventListener('hidden.bs.modal', function () {
    checkoutRentalId = null;
    checkoutEndedAt = null;
    checkoutGrandTotal = 0;
    checkoutTokoId = 0;
    if (checkoutCancelBtn) checkoutCancelBtn.disabled = false;
    resetAdditionalQty();
    resetCheckoutPaymentFields();
  });

  const quickAddItemModalEl = document.getElementById('quickAddItemModal');
  const quickAddItemModal = quickAddItemModalEl ? new bootstrap.Modal(quickAddItemModalEl) : null;
  const quickItemNamaEl = document.getElementById('quick_item_nama');
  const quickItemHargaEl = document.getElementById('quick_item_harga');
  const quickAddItemAlert = document.getElementById('quickAddItemAlert');
  const quickAddItemSaveBtn = document.getElementById('quickAddItemSaveBtn');

  function showQuickAddAlert(msg) {
    if (!quickAddItemAlert) return;
    quickAddItemAlert.textContent = msg || 'Terjadi kesalahan.';
    quickAddItemAlert.classList.remove('d-none');
  }

  function hideQuickAddAlert() {
    if (!quickAddItemAlert) return;
    quickAddItemAlert.classList.add('d-none');
    quickAddItemAlert.textContent = '';
  }

  function openQuickAddItemModal() {
    hideQuickAddAlert();
    if (quickItemNamaEl) quickItemNamaEl.value = '';
    if (quickItemHargaEl) quickItemHargaEl.value = '';
    const tokoId = canSeeAllTokoJs
      ? (quickAddTokoId || checkoutTokoId || itemsTokoId || 0)
      : (userIdToko || 0);
    if (canSeeAllTokoJs && !tokoId) {
      AppToast.show('Pilih meja / buka sewa terlebih dahulu agar toko item diketahui.', 'danger');
      return;
    }
    if (!canSeeAllTokoJs && !userIdToko) {
      AppToast.show('Akun belum terhubung ke toko.', 'danger');
      return;
    }
    quickAddItemModal?.show();
    setTimeout(function () { quickItemNamaEl?.focus(); }, 200);
  }

  function buildItemRowHtml(item, qtyClass, lineClass) {
    const harga = Number(item.harga) || 0;
    return '<tr data-item-id="' + item.id + '" data-item-harga="' + harga + '" data-item-discount="0" data-item-toko="' + (item.id_toko || 0) + '">' +
      '<td>' + escapeHtml(item.nama) + '</td>' +
      '<td class="text-end font-monospace small">' + fmtRp(harga) + '</td>' +
      '<td><input type="number" class="form-control form-control-sm ' + qtyClass + '" min="0" max="999" value="0" data-item-id="' + item.id + '" /></td>' +
      '<td class="text-end font-monospace small ' + lineClass + '">Rp 0</td>' +
      '</tr>';
  }

  function appendMasterItem(item) {
    masterItems.push(item);
    const midTbody = document.querySelector('#midItemsTable tbody');
    const checkoutTbody = document.querySelector('#additionalItemsTable tbody');
    if (midTbody) midTbody.insertAdjacentHTML('beforeend', buildItemRowHtml(item, 'mid-item-qty', 'mid-item-line-total'));
    if (checkoutTbody) checkoutTbody.insertAdjacentHTML('beforeend', buildItemRowHtml(item, 'additional-qty', 'additional-line-total'));
    document.getElementById('itemsEmpty')?.classList.toggle('d-none', masterItems.length > 0);
    document.getElementById('additionalItemsEmpty')?.classList.toggle('d-none', masterItems.length > 0);
    filterItemRows('#midItemsTable', itemsTokoId || quickAddTokoId);
    filterItemRows('#additionalItemsTable', checkoutTokoId || quickAddTokoId);
  }

  document.getElementById('midTambahItemBtn')?.addEventListener('click', openQuickAddItemModal);
  document.getElementById('checkoutTambahItemBtn')?.addEventListener('click', openQuickAddItemModal);

  quickAddItemSaveBtn?.addEventListener('click', function () {
    const nama = (quickItemNamaEl?.value || '').trim();
    const harga = parseFloat(quickItemHargaEl?.value ?? '');
    hideQuickAddAlert();
    if (!nama) {
      showQuickAddAlert('Nama wajib diisi.');
      quickItemNamaEl?.focus();
      return;
    }
    if (!Number.isFinite(harga) || harga < 0) {
      showQuickAddAlert('Harga wajib diisi (min. 0).');
      quickItemHargaEl?.focus();
      return;
    }

    const payload = { nama: nama, harga: harga, is_discount: false };
    if (canSeeAllTokoJs) {
      payload.id_toko = quickAddTokoId || checkoutTokoId || itemsTokoId || 0;
      if (!payload.id_toko) {
        showQuickAddAlert('Toko tidak diketahui. Buka dari meja yang dipilih.');
        return;
      }
    }

    const btn = quickAddItemSaveBtn;
    btn.disabled = true;
    fetch(routes.quickAddItem, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
      .then(function (res) { return res.json().then(function (body) { return { ok: res.ok, status: res.status, body: body }; }); })
      .then(function (r) {
        btn.disabled = false;
        if (!r.ok) {
          const msg = r.status === 422 && r.body?.errors
            ? (Object.values(r.body.errors)[0]?.[0] || 'Validasi gagal.')
            : (r.body?.message || 'Gagal menambah item.');
          showQuickAddAlert(msg);
          return;
        }
        if (r.body?.item) appendMasterItem(r.body.item);
        quickAddItemModal?.hide();
        AppToast.show(r.body?.message || 'Item ditambahkan.', 'success');
        if (checkoutRentalId) refreshCheckoutPreview();
      })
      .catch(function () {
        btn.disabled = false;
        showQuickAddAlert('Jaringan bermasalah.');
      });
  });
})();
</script>
@endpush
