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
</style>
@endpush

@section('content')
  <header class="page-header text-center mb-4">
    <a href="{{ route('home') }}" class="btn btn-sm btn-outline-secondary mb-3">
      <i class="bi bi-arrow-left me-1"></i>Kembali
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
      Periksa Nomor HP
    </div>
    <div class="card-body p-4">
      <p class="text-secondary small mb-4">
        Masukkan nomor HP yang akan digunakan untuk pendaftaran. Kami akan memeriksa apakah Anda sudah terdaftar di turnamen ini.
      </p>

      @if ($errors->has('no_hp'))
        <div class="alert alert-danger">{{ $errors->first('no_hp') }}</div>
      @endif

      <form method="post" action="{{ route('public.mahjong-tournaments.register.check', $tournament['id']) }}" novalidate>
        @csrf
        <div class="mb-4">
          <x-phone-input
            name="no_hp"
            id="check_no_hp"
            :value="old('no_hp')"
          />
        </div>

        <button type="submit" class="btn btn-primary btn-submit w-100">
          <i class="bi bi-search me-1"></i>Lanjutkan
        </button>
      </form>
    </div>
  </div>
@endsection
