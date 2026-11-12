@extends('layouts.public')

@section('title', 'Omahjong - Turnamen & Panduan Mahjong')

@push('styles')
<style>
  .welcome { text-align: center; padding: 2rem 0 1rem; }
  .welcome h1 { color: var(--brand); font-weight: 700; }
  .welcome p { color: #6c757d; }
  .home-choices { max-width: 800px; margin: 1rem auto; }
  .home-choice {
    display: flex; flex-direction: column; align-items: flex-start;
    height: 100%; padding: 2rem; border-radius: 1rem;
    background: #fff; border: 1px solid rgba(0, 97, 49, 0.15);
    box-shadow: 0 8px 24px rgba(0, 60, 30, 0.06);
    color: #1f2937; text-decoration: none;
  }
  .home-choice:hover, .home-choice:focus-visible {
    color: #1f2937; border-color: var(--brand);
    box-shadow: 0 12px 28px rgba(0, 60, 30, 0.12);
  }
  .home-choice:focus-visible { outline: 3px solid var(--brand); outline-offset: 4px; }
  .home-choice > i { color: var(--brand); font-size: 2.5rem; margin-bottom: 1rem; }
  .home-choice h2 { font-size: 1.5rem; color: var(--brand); font-weight: 700; }
  .home-choice p { color: #6c757d; margin-bottom: 1.5rem; }
  .home-choice span { margin-top: auto; color: var(--brand); font-weight: 600; }
</style>
@endpush

@section('content')
  <header class="welcome">
    <h1>Selamat datang di Omahjong</h1>
    <p>Mau lihat turnamen atau pelajari panduan Mahjong?</p>
  </header>
  <nav class="home-choices row g-3" aria-label="Pilihan halaman Mahjong">
    <div class="col-md-6">
      <a class="home-choice" href="{{ route('public.mahjong-tournaments') }}">
        <i class="bi bi-trophy-fill" aria-hidden="true"></i>
        <h2>Turnamen</h2>
        <p>Lihat jadwal, daftar turnamen, dan pantau klasemen serta juara.</p>
        <span>Lihat turnamen <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
      </a>
    </div>
    <div class="col-md-6">
      <a class="home-choice" href="{{ route('public.mahjong-tournaments', ['tab' => 'panduan']) }}">
        <i class="bi bi-journal-text" aria-hidden="true"></i>
        <h2>Panduan</h2>
        <p>Kenali ubin, cara bermain, kombinasi tangan, dan aturan Omahjong.</p>
        <span>Buka panduan <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
      </a>
    </div>
  </nav>
@endsection
