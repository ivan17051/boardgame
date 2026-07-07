@extends('layouts.public')

@section('title', 'Status Pendaftaran — ' . ($tournament['nama'] ?? 'Turnamen Mahjong') . ' — Omahjong')

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
  .status-list dt {
    color: #6c757d;
    font-weight: 500;
  }
  .status-list dd {
    font-weight: 600;
    color: var(--brand-dark);
  }
  .status-badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 999px;
    background: rgba(0, 97, 49, 0.1);
    color: var(--brand-dark);
    font-size: 0.9rem;
  }
  .upload-box {
    border-top: 1px solid rgba(0, 97, 49, 0.1);
    margin-top: 1.25rem;
    padding-top: 1.25rem;
  }
  .player-foto {
    width: 110px;
    height: 110px;
    object-fit: cover;
    border-radius: 50%;
    border: 3px solid #fff;
    box-shadow: 0 6px 20px rgba(0, 60, 30, 0.12);
  }
</style>
@endpush

@section('content')
  <header class="page-header text-center mb-4">
    <a href="{{ route('home') }}" class="btn btn-sm btn-outline-secondary mb-3">
      <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
    <h1>
      <i class="bi {{ ! empty($justRegistered) ? 'bi-check-circle' : 'bi-person-check' }} me-2"></i>
      {{ ! empty($justRegistered) ? 'Pendaftaran Berhasil' : 'Status Pendaftaran' }}
    </h1>
    <p>{{ $tournament['nama'] ?? 'Turnamen Mahjong' }}</p>
  </header>

  <div class="card register-card">
    <div class="card-header py-3">
      {{ ! empty($justRegistered) ? 'Terima kasih telah mendaftar' : 'Anda sudah terdaftar' }}
    </div>
    <div class="card-body p-4">
      @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif

      <div class="alert alert-{{ ! empty($justRegistered) ? 'success' : 'info' }} mb-4">
        <i class="bi bi-info-circle me-1"></i>
        @if (! empty($justRegistered))
          Pendaftaran untuk nomor HP <strong>{{ $check['no_hp'] ?? '—' }}</strong> berhasil dikirim.
        @else
          Nomor HP <strong>{{ $check['no_hp'] ?? '—' }}</strong> sudah terdaftar di turnamen ini.
        @endif
      </div>

      @if (! empty($check['foto_url']))
        <div class="text-center mb-4">
          <img src="{{ $check['foto_url'] }}" alt="Foto {{ $check['nama'] ?? 'pemain' }}" class="player-foto" />
        </div>
      @endif

      <dl class="row status-list mb-0">
        <dt class="col-sm-4">Nama</dt>
        <dd class="col-sm-8">{{ $check['nama'] ?? '—' }}</dd>

        <dt class="col-sm-4">Jenis kelamin</dt>
        <dd class="col-sm-8">{{ $genderLabel }}</dd>

        <dt class="col-sm-4">Status pendaftaran</dt>
        <dd class="col-sm-8">
          <span class="status-badge">{{ $statusLabel }}</span>
        </dd>

        @if (! empty($check['bukti_bayar_url']))
          <dt class="col-sm-4">Bukti bayar</dt>
          <dd class="col-sm-8">
            <a href="{{ $check['bukti_bayar_url'] }}" target="_blank" rel="noopener noreferrer">
              Lihat bukti bayar <i class="bi bi-box-arrow-up-right ms-1"></i>
            </a>
          </dd>
        @endif
      </dl>

      @if (! empty($canUploadReceipt))
        <div class="upload-box">
          <h2 class="h6 fw-bold text-brand-dark mb-2">
            <i class="bi bi-upload me-1"></i>Unggah Bukti Bayar
          </h2>
          <p class="text-secondary small mb-3">
            Unggah bukti pembayaran biaya turnamen. Format JPG, PNG, WebP, atau PDF. Maks. 5 MB.
          </p>

          @if ($errors->has('bukti_bayar'))
            <div class="alert alert-danger">{{ $errors->first('bukti_bayar') }}</div>
          @endif

          <form
            method="post"
            action="{{ route('public.mahjong-tournaments.register.receipt', $tournament['id']) }}"
            enctype="multipart/form-data"
            novalidate
          >
            @csrf
            <div class="mb-3">
              <input
                type="file"
                name="bukti_bayar"
                id="bukti_bayar"
                class="form-control @error('bukti_bayar') is-invalid @enderror"
                accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf"
                required
              />
              @error('bukti_bayar')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <button type="submit" class="btn btn-primary w-100">
              <i class="bi bi-cloud-upload me-1"></i>Kirim Bukti Bayar
            </button>
          </form>
        </div>
      @endif

      <div class="d-grid gap-2 mt-4">
        <a href="{{ route('home') }}" class="btn btn-outline-primary">
          <i class="bi bi-house me-1"></i>Kembali ke beranda
        </a>
        <a href="{{ route('public.mahjong-tournaments.register', $tournament['id']) }}" class="btn btn-outline-secondary">
          Periksa nomor HP lain
        </a>
      </div>
    </div>
  </div>
@endsection
