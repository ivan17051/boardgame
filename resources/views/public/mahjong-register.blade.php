@extends('layouts.public')

@section('title', 'Daftar — ' . ($tournament['nama'] ?? 'Turnamen Mahjong') . ' — Omahjong')

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
  .syarat-box {
    background: rgba(0, 97, 49, 0.04);
    border: 1px solid rgba(0, 97, 49, 0.12);
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1.25rem;
  }
  .syarat-box h2 {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--brand-dark);
    margin: 0 0 0.5rem;
  }
  .syarat-box p {
    color: #6c757d;
    font-size: 0.9rem;
    margin: 0;
    white-space: pre-line;
  }
</style>
@endpush

@section('content')
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
  </header>

  <div class="card register-card">
    <div class="card-header py-3">
      Formulir Pendaftaran Pemain
    </div>
    <div class="card-body p-4">
      @if (! empty($tournament['syarat']))
        <div class="syarat-box">
          <h2><i class="bi bi-info-circle me-1"></i>Syarat &amp; Ketentuan</h2>
          <p>{{ $tournament['syarat'] }}</p>
        </div>
      @endif

      @if (! empty($pemainExists))
        <div class="alert alert-info py-2 small">
          Nama dan jenis kelamin sudah diisi dari data pemain. Anda dapat mengubahnya jika perlu.
        </div>
      @endif

      @if ($errors->has('form'))
        <div class="alert alert-danger">{{ $errors->first('form') }}</div>
      @endif

      <form method="post" action="{{ route('public.mahjong-tournaments.register.store', $tournament['id']) }}" novalidate>
        @csrf
        <input type="hidden" name="id_turnamen" value="{{ $tournament['id'] }}" />
        <input type="hidden" name="no_hp" value="{{ $prefillNoHp }}" />

        <div class="mb-3">
          <label class="form-label fw-semibold">Nomor HP</label>
          <input type="text" class="form-control bg-light" value="{{ $prefillNoHp }}" readonly />
        </div>

        <div class="mb-3">
          <label for="nama" class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
          <input
            type="text"
            name="nama"
            id="nama"
            class="form-control @error('nama') is-invalid @enderror"
            value="{{ $prefillNama }}"
            placeholder="Masukkan nama lengkap"
            required
            autocomplete="name"
          />
          @error('nama')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="mb-3">
          <label for="gender" class="form-label fw-semibold">Jenis Kelamin <span class="text-danger">*</span></label>
          <select
            name="gender"
            id="gender"
            class="form-select @error('gender') is-invalid @enderror"
            required
          >
            <option value="" disabled {{ $prefillGender ? '' : 'selected' }}>Pilih jenis kelamin</option>
            <option value="male" {{ $prefillGender === 'male' ? 'selected' : '' }}>Laki-laki</option>
            <option value="female" {{ $prefillGender === 'female' ? 'selected' : '' }}>Perempuan</option>
          </select>
          @error('gender')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="mb-4">
          <label for="tgl_lahir" class="form-label fw-semibold">
            Tanggal Lahir <span class="text-muted fw-normal">(opsional)</span>
          </label>
          <input
            type="date"
            name="tgl_lahir"
            id="tgl_lahir"
            class="form-control @error('tgl_lahir') is-invalid @enderror"
            value="{{ old('tgl_lahir') }}"
            max="{{ date('Y-m-d', strtotime('-1 day')) }}"
          />
          @error('tgl_lahir')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-submit w-100">
          <i class="bi bi-send me-1"></i>Kirim Pendaftaran
        </button>
      </form>
    </div>
  </div>
@endsection
