@extends('layouts.public')

@section('title', 'Daftar — ' . ($tournament['nama'] ?? 'Turnamen Mahjong') . ' — Omahjong')

@section('og')
  @include('public.partials.og-meta', [
    'ogTournament' => $tournament,
    'ogUrl' => route('public.mahjong-tournaments.register', $tournament['id']),
    'ogTitle' => 'Daftar ' . ($tournament['nama'] ?? 'Turnamen Mahjong') . ' — Omahjong',
    'ogDescription' => trim(implode(' · ', array_filter([
      'Pendaftaran turnamen mahjong',
      ! empty($tournament['tanggal'])
        ? \Carbon\Carbon::parse($tournament['tanggal'])->locale('id')->translatedFormat('d F Y')
        : null,
      isset($tournament['harga']) && (float) $tournament['harga'] > 0
        ? 'Rp ' . number_format((float) $tournament['harga'], 0, ',', '.')
        : 'Gratis',
    ]))),
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
</style>
@endpush

@section('content')
  @php
    $idKategori = $idKategori ?? $tournament['default_kategori_id'] ?? null;
  @endphp

  <header class="page-header text-center mb-4">
    <a href="{{ route('home') }}" class="btn btn-sm btn-outline-secondary mb-3">
      <i class="bi bi-house me-1"></i>Beranda
    </a>
    <h1><i class="bi bi-person-plus me-2"></i>Daftar Turnamen</h1>
    <p>{{ $tournament['nama'] ?? 'Turnamen Mahjong' }}</p>
    @if (! empty($tournament['tanggal']))
      <p class="small text-secondary mb-0">
        <i class="bi bi-calendar3 me-1"></i>
        {{ \Carbon\Carbon::parse($tournament['tanggal'])->locale('id')->translatedFormat('d F Y') }}
      </p>
    @endif
    @include('public.partials.tournament-syarat', ['tournament' => $tournament])
  </header>

  @include('public.partials.tournament-nav', [
    'tournament' => $tournament,
    'idKategori' => $idKategori,
    'activeTab' => 'register',
  ])

  <div class="card register-card {{ ($allowsGroup ?? ! empty($tournament['allows_group_registration'])) ? 'is-wide' : '' }}">
    <div class="card-header py-3">
      Periksa Nomor HP
    </div>
    <div class="card-body p-4">
      @php
        $allowsGroup = $allowsGroup ?? ! empty($tournament['allows_group_registration']);
        $groupSize = (int) ($groupSize ?? $tournament['registration_roster_size'] ?? 4);
        $rosterNoun = $rosterNoun ?? ($tournament['registration_roster_noun'] ?? 'tim');
        $rosterNounTitle = $rosterNounTitle ?? ucfirst($rosterNoun);
        $registrationMode = old('registration_mode', 'single');
        $isGroupMode = $allowsGroup && $registrationMode === 'group';
        $maxRoster = \App\Support\BornpadelMahjongTournaments::MAHJONG_TEAM_MAX_PLAYERS_PER_TEAM;
      @endphp

      <p class="text-secondary small mb-4">
        @if ($allowsGroup)
          Mahjong Tim: daftar individu, atau daftar satu {{ $rosterNoun }} lengkap ({{ $groupSize }} pemain + nama {{ $rosterNoun }}).
        @else
          Masukkan nomor HP yang akan digunakan untuk pendaftaran. Kami akan memeriksa apakah Anda sudah terdaftar di turnamen ini.
        @endif
      </p>

      @if ($errors->any() && ! $errors->has('no_hp') && ! $errors->has('id_kategori') && ! $errors->has('nama_grup'))
        <div class="alert alert-danger">{{ $errors->first() }}</div>
      @endif

      <form
        method="post"
        action="{{ route('public.mahjong-tournaments.register.check', $tournament['id']) }}"
        novalidate
        id="register-lookup-form"
        data-group-size="{{ $groupSize }}"
      >
        @csrf

        @if (! empty($tournament['has_multiple_kategori']))
          <div class="mb-3">
            <label for="id_kategori" class="form-label fw-semibold">Kategori <span class="text-danger">*</span></label>
            <select
              name="id_kategori"
              id="id_kategori"
              class="form-select @error('id_kategori') is-invalid @enderror"
              required
            >
              <option value="" disabled {{ old('id_kategori') ? '' : 'selected' }}>Pilih kategori</option>
              @foreach (($tournament['kategori'] ?? []) as $kat)
                <option
                  value="{{ $kat['id'] }}"
                  data-roster-size="{{ $kat['registration_roster_size'] ?? $groupSize }}"
                  {{ (string) old('id_kategori', $idKategori ?? $tournament['default_kategori_id'] ?? '') === (string) $kat['id'] ? 'selected' : '' }}
                >
                  {{ $kat['nama'] }}
                </option>
              @endforeach
            </select>
            @error('id_kategori')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        @elseif (! empty($tournament['default_kategori_id']))
          <input type="hidden" name="id_kategori" value="{{ $tournament['default_kategori_id'] }}" />
        @endif

        @if ($allowsGroup)
          <div class="mb-4">
            <div class="form-label fw-semibold mb-2">Mode Pendaftaran</div>
            <div class="btn-group w-100" role="group" aria-label="Mode pendaftaran">
              <input type="radio"
                     class="btn-check"
                     name="registration_mode"
                     id="registration-mode-single"
                     value="single"
                     autocomplete="off"
                     {{ $isGroupMode ? '' : 'checked' }}>
              <label class="btn btn-outline-success" for="registration-mode-single">Individu</label>

              <input type="radio"
                     class="btn-check"
                     name="registration_mode"
                     id="registration-mode-group"
                     value="group"
                     autocomplete="off"
                     {{ $isGroupMode ? 'checked' : '' }}>
              <label class="btn btn-outline-success" for="registration-mode-group">Satu {{ $rosterNounTitle }} ({{ $groupSize }} pemain)</label>
            </div>
            @error('registration_mode')
              <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
          </div>
        @else
          <input type="hidden" name="registration_mode" value="single" />
        @endif

        <div class="mb-4" id="nama-grup-section" style="{{ $isGroupMode ? '' : 'display:none' }}">
          <label for="nama_grup" class="form-label fw-semibold">Nama {{ $rosterNounTitle }} <span class="text-danger">*</span></label>
          <input
            type="text"
            name="nama_grup"
            id="nama_grup"
            class="form-control @error('nama_grup') is-invalid @enderror"
            value="{{ old('nama_grup') }}"
            maxlength="255"
            placeholder="Contoh: Dragon Squad"
          />
          @error('nama_grup')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="mb-4">
          <x-phone-input
            name="no_hp"
            id="check_no_hp"
            :label="$allowsGroup ? 'Nomor HP Pemain 1' : 'Nomor HP / WhatsApp'"
            :value="old('no_hp')"
          />
        </div>

        @if ($allowsGroup)
          @for ($n = 2; $n <= $maxRoster; $n++)
            @php $showPhone = $isGroupMode && $n <= $groupSize; @endphp
            <div id="player-{{ $n }}-phone-section"
                 class="mb-4 group-extra-phone {{ $showPhone ? '' : 'd-none' }}"
                 data-player-index="{{ $n }}">
              <x-phone-input
                name="no_hp_{{ $n }}"
                id="check_no_hp_{{ $n }}"
                :label="'Nomor HP Pemain '.$n"
                :value="old('no_hp_'.$n)"
                :required="$showPhone"
                :error-key="'no_hp_'.$n"
              />
            </div>
          @endfor
        @endif

        <button type="submit" class="btn btn-primary btn-submit w-100">
          <i class="bi bi-search me-1"></i>Lanjutkan
        </button>
      </form>
    </div>
  </div>
@endsection

@if ($allowsGroup ?? ! empty($tournament['allows_group_registration']))
@push('scripts')
<script>
  (function () {
    const form = document.getElementById('register-lookup-form');
    if (!form) return;

    const groupSizeDefault = parseInt(form.dataset.groupSize || '4', 10) || 4;
    const namaGrupSection = document.getElementById('nama-grup-section');
    const namaGrupInput = document.getElementById('nama_grup');
    const groupModeLabel = document.querySelector('label[for="registration-mode-group"]');
    const kategoriSelect = document.getElementById('id_kategori');
    const extraPhoneSections = form.querySelectorAll('.group-extra-phone');

    const currentGroupSize = () => {
      const selected = kategoriSelect && kategoriSelect.selectedOptions[0];
      const fromKat = selected ? parseInt(selected.getAttribute('data-roster-size') || '', 10) : NaN;
      return Number.isFinite(fromKat) && fromKat > 0 ? fromKat : groupSizeDefault;
    };

    const syncMode = () => {
      const mode = form.querySelector('input[name="registration_mode"]:checked')?.value || 'single';
      const isGroup = mode === 'group';
      const groupSize = currentGroupSize();

      extraPhoneSections.forEach((section) => {
        const index = parseInt(section.dataset.playerIndex || '0', 10);
        const localInput = section.querySelector('[data-phone-local]');
        const hiddenInput = section.querySelector('[data-phone-hidden]');
        const show = isGroup && index <= groupSize;
        section.classList.toggle('d-none', !show);
        if (localInput) {
          localInput.required = show;
          if (!show) localInput.value = '';
        }
        if (!show && hiddenInput) hiddenInput.value = '';
      });

      if (namaGrupSection) {
        namaGrupSection.style.display = isGroup ? '' : 'none';
      }
      if (namaGrupInput) {
        namaGrupInput.required = isGroup;
        if (!isGroup) namaGrupInput.value = '';
      }
      if (groupModeLabel) {
        groupModeLabel.textContent = 'Satu {{ $rosterNounTitle }} (' + groupSize + ' pemain)';
      }
    };

    form.querySelectorAll('input[name="registration_mode"]').forEach((input) => {
      input.addEventListener('change', syncMode);
    });
    if (kategoriSelect) {
      kategoriSelect.addEventListener('change', syncMode);
    }
    syncMode();
  })();
</script>
@endpush
@endif
