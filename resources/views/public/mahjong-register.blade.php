@extends('layouts.public')

@section('title', 'Daftar — ' . ($tournament['nama'] ?? 'Turnamen Mahjong') . ' — Omahjong')

@section('og')
  @include('public.partials.og-meta', [
    'ogTournament' => $tournament,
    'ogUrl' => route('public.mahjong-tournaments.register', $tournament['id']),
    'ogTitle' => 'Daftar ' . ($tournament['nama'] ?? 'Turnamen Mahjong') . ' — Omahjong',
    'ogImage' => $tournament['share_image_url']
      ?? \App\Support\BornpadelMahjongTournaments::tournamentShareImageUrl($tournament['foto'] ?? null),
  ])
@endsection

@push('styles')
<style>
  .register-card {
    max-width: 520px;
    margin: 0 auto;
    border: 1px solid rgba(0, 97, 49, 0.12);
    border-radius: 1rem;
    box-shadow: 0 8px 24px rgba(0, 60, 30, 0.06);
    overflow: hidden;
  }
  .register-card.is-wide {
    max-width: 640px;
  }
  .register-card .card-header {
    background: rgba(0, 97, 49, 0.06);
    font-weight: 700;
    color: var(--brand-dark);
  }
  .page-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--brand);
    margin: 0;
  }
  .page-header p {
    color: #6c757d;
    margin: 0.35rem 0 0;
  }
  .btn-submit {
    background: var(--brand);
    border-color: var(--brand);
  }
  .btn-submit:hover {
    background: var(--brand-dark);
    border-color: var(--brand-dark);
  }
  .foto-preview {
    display: none;
    margin-top: 0.75rem;
  }
  .foto-preview img {
    width: 96px;
    height: 96px;
    object-fit: cover;
    border-radius: 0.75rem;
    border: 1px solid rgba(0, 97, 49, 0.15);
    box-shadow: 0 4px 14px rgba(0, 60, 30, 0.08);
  }
  .bukti-preview {
    display: none;
    margin-top: 0.75rem;
  }
  .bukti-preview img {
    max-width: 100%;
    max-height: 220px;
    object-fit: contain;
    border-radius: 0.75rem;
    border: 1px solid rgba(0, 97, 49, 0.15);
    box-shadow: 0 4px 14px rgba(0, 60, 30, 0.08);
    background: #f8f9fa;
  }
</style>
@endpush

@section('content')
  @php
    $isGroupMode = $isGroupMode ?? false;
    $groupSize = (int) ($groupSize ?? 1);
    $players = $players ?? [];
    $rosterNounTitle = $rosterNounTitle ?? 'Tim';
    $namaGrup = $namaGrup ?? '';
  @endphp

  <header class="page-header text-center mb-4">
    <a href="{{ route('public.mahjong-tournaments.register', $tournament['id']) }}" class="btn btn-sm btn-outline-secondary mb-3">
      <i class="bi bi-arrow-left me-1"></i>Ganti nomor HP
    </a>
    <h1><i class="bi bi-person-plus me-2"></i>Daftar Turnamen</h1>
    <p>{{ $tournament['nama'] ?? 'Turnamen Mahjong' }}</p>
    @if (! empty($tournament['tanggal']))
      <p class="small text-secondary mb-0">
        <i class="bi bi-calendar3 me-1"></i>
        {{ \Carbon\Carbon::parse($tournament['tanggal'])->locale('id')->translatedFormat('d F Y') }}
      </p>
    @endif
    @if ($isGroupMode)
      <p class="small mb-0 mt-2">
        <span class="badge text-bg-success">Pendaftaran Satu {{ $rosterNounTitle }} ({{ $groupSize }})</span>
      </p>
    @endif
    @include('public.partials.tournament-syarat', ['tournament' => $tournament])
  </header>

  @include('public.partials.tournament-nav', [
    'tournament' => $tournament,
    'idKategori' => $idKategori ?? $check['id_kategori'] ?? $tournament['default_kategori_id'] ?? null,
    'activeTab' => 'register',
  ])

  <div class="card register-card {{ $isGroupMode ? 'is-wide' : '' }}">
    <div class="card-header py-3">
      {{ $isGroupMode ? 'Formulir Pendaftaran '.$rosterNounTitle : 'Formulir Pendaftaran Pemain' }}
    </div>
    <div class="card-body p-4">
      @if (! $isGroupMode && ! empty($pemainExists))
        <div class="alert alert-info py-2 small">
          Nama dan jenis kelamin sudah diisi dari data pemain. Anda dapat mengubahnya jika perlu.
        </div>
      @endif

      @if ($errors->has('form'))
        <div class="alert alert-danger">{{ $errors->first('form') }}</div>
      @endif

      <form
        method="post"
        action="{{ route('public.mahjong-tournaments.register.store', $tournament['id']) }}"
        enctype="multipart/form-data"
        novalidate
      >
        @csrf
        <input type="hidden" name="id_turnamen" value="{{ $tournament['id'] }}" />
        <input type="hidden" name="registration_mode" value="{{ $isGroupMode ? 'group' : 'single' }}" />
        @if (! empty($idKategori))
          <input type="hidden" name="id_kategori" value="{{ $idKategori }}" />
        @endif

        @if ($isGroupMode)
          <input type="hidden" name="nama_grup" value="{{ old('nama_grup', $namaGrup) }}" />
          <div class="mb-4">
            <label class="form-label fw-semibold">Nama {{ $rosterNounTitle }}</label>
            <input type="text" class="form-control bg-light" value="{{ old('nama_grup', $namaGrup) }}" readonly />
            @error('nama_grup')
              <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
          </div>

          @foreach ($players as $index => $player)
            @include('public.partials.mahjong-register-player-fields', [
              'index' => $index,
              'player' => $player,
              'showHeading' => true,
            ])
          @endforeach
        @else
          @include('public.partials.mahjong-register-player-fields', [
            'index' => 0,
            'player' => [
              'no_hp' => $prefillNoHp,
              'nama' => $prefillNama,
              'gender' => $prefillGender,
              'pemain_exists' => false,
            ],
            'showHeading' => false,
          ])
        @endif

        <div class="mb-4 pt-3 border-top">
          <label for="bukti_bayar" class="form-label fw-semibold">
            Bukti Transfer <span class="text-muted fw-normal">(opsional)</span>
          </label>
          <input
            type="file"
            name="bukti_bayar"
            id="bukti_bayar"
            class="form-control js-bukti-input @error('bukti_bayar') is-invalid @enderror"
            accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf"
            data-preview="#buktiPreview"
            data-preview-img="#buktiPreviewImg"
            data-preview-name="#buktiPreviewName"
          />
          <div class="form-text">
            Unggah bukti pembayaran biaya turnamen. Format JPG, PNG, WebP, atau PDF. Maks. 5 MB.
            @if (isset($tournament['harga']) && (float) $tournament['harga'] > 0)
              Biaya: Rp {{ number_format((float) $tournament['harga'], 0, ',', '.') }}.
            @endif
            Bisa juga diunggah nanti setelah pendaftaran.
          </div>
          @error('bukti_bayar')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
          <div class="bukti-preview" id="buktiPreview">
            <img src="" alt="Pratinjau bukti transfer" id="buktiPreviewImg" />
            <div class="small text-secondary mt-2" id="buktiPreviewName"></div>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-submit w-100">
          <i class="bi bi-send me-1"></i>{{ $isGroupMode ? 'Kirim Pendaftaran Tim' : 'Kirim Pendaftaran' }}
        </button>
      </form>
    </div>
  </div>
@endsection

@push('scripts')
<script>
  (function () {
    document.querySelectorAll('.js-foto-input').forEach((input) => {
      const preview = document.querySelector(input.dataset.preview || '');
      const previewImg = document.querySelector(input.dataset.previewImg || '');
      if (!preview || !previewImg) return;

      input.addEventListener('change', () => {
        const file = input.files && input.files[0];
        if (!file || !file.type.startsWith('image/')) {
          preview.style.display = 'none';
          previewImg.src = '';
          return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
          previewImg.src = e.target.result;
          preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
      });
    });

    document.querySelectorAll('.js-bukti-input').forEach((input) => {
      const preview = document.querySelector(input.dataset.preview || '');
      const previewImg = document.querySelector(input.dataset.previewImg || '');
      const previewName = document.querySelector(input.dataset.previewName || '');
      if (!preview) return;

      input.addEventListener('change', () => {
        const file = input.files && input.files[0];
        if (!file) {
          preview.style.display = 'none';
          if (previewImg) previewImg.src = '';
          if (previewName) previewName.textContent = '';
          return;
        }

        preview.style.display = 'block';
        if (previewName) previewName.textContent = file.name;
        if (previewImg) {
          if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
              previewImg.src = e.target.result;
              previewImg.style.display = 'block';
            };
            reader.readAsDataURL(file);
          } else {
            previewImg.src = '';
            previewImg.style.display = 'none';
          }
        }
      });
    });
  })();
</script>
@endpush
