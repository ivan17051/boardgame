@php
    $tournament = $tournament ?? [];
    $tournamentId = (int) ($tournament['id'] ?? 0);
    $activeTab = $activeTab ?? null;
    $idKategori = $idKategori ?? request('id_kategori');
    $status = $tournament['status'] ?? null;
    $registrationOpen = ! empty($tournament['registration_open']) || $status === 'open';

    $query = [];
    if (! empty($tournament['has_multiple_kategori']) && $idKategori) {
        $query['id_kategori'] = (int) $idKategori;
    }

    $tabs = [];

    if ($tournamentId > 0 && ($registrationOpen || $activeTab === 'register')) {
        $tabs[] = [
            'key' => 'register',
            'label' => 'Daftar',
            'icon' => 'bi-pencil-square',
            'url' => route('public.mahjong-tournaments.register', array_merge(['id' => $tournamentId], $query)),
        ];
    }

    if ($tournamentId > 0) {
        $tabs[] = [
            'key' => 'participants',
            'label' => 'Peserta',
            'icon' => 'bi-people',
            'url' => route('public.mahjong-tournaments.participants', array_merge(['id' => $tournamentId], $query)),
        ];

        if (in_array($status, ['ongoing', 'completed'], true) || $activeTab === 'groups') {
            $tabs[] = [
                'key' => 'groups',
                'label' => 'Grup',
                'icon' => 'bi-grid-3x3-gap',
                'url' => route('public.mahjong-tournaments.groups', array_merge(['id' => $tournamentId], $query)),
            ];
        }

        $tabs[] = [
            'key' => 'standings',
            'label' => 'Klasemen',
            'icon' => 'bi-bar-chart-line',
            'url' => route('public.mahjong-tournaments.standings', array_merge(['id' => $tournamentId], $query)),
        ];
    }
@endphp

@if (count($tabs) > 0)
@once
@push('styles')
<style>
  .guest-tournament-nav {
    display: flex;
    flex-wrap: nowrap;
    gap: 0.4rem;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    padding: 0.35rem;
    width: 100%;
    max-width: 42rem;
    margin: 0 auto 1.25rem;
    background: #fff;
    border: 1px solid rgba(0, 97, 49, 0.12);
    border-radius: 0.85rem;
    box-shadow: 0 2px 12px rgba(0, 60, 30, 0.04);
    scrollbar-width: thin;
  }
  .guest-tournament-nav::-webkit-scrollbar {
    height: 4px;
  }
  .guest-tournament-nav::-webkit-scrollbar-thumb {
    background: rgba(0, 97, 49, 0.2);
    border-radius: 4px;
  }
  .guest-tournament-nav__link {
    flex: 1 0 auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    padding: 0.55rem 0.9rem;
    border-radius: 0.6rem;
    font-weight: 600;
    font-size: 0.9rem;
    color: #5c5c5c;
    text-decoration: none;
    white-space: nowrap;
    border: 1px solid transparent;
    transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
  }
  .guest-tournament-nav__link:hover {
    color: var(--brand-dark);
    background: rgba(0, 97, 49, 0.08);
  }
  .guest-tournament-nav__link.is-active {
    color: #fff;
    background: var(--brand);
    border-color: var(--brand);
  }
  .guest-tournament-nav__link i {
    font-size: 1rem;
  }
  @media (max-width: 576px) {
    .guest-tournament-nav__label {
      font-size: 0.82rem;
    }
  }
</style>
@endpush
@endonce

<nav class="guest-tournament-nav" aria-label="Menu turnamen">
  @foreach ($tabs as $tab)
    <a href="{{ $tab['url'] }}"
       class="guest-tournament-nav__link {{ $activeTab === $tab['key'] ? 'is-active' : '' }}"
       @if ($activeTab === $tab['key']) aria-current="page" @endif>
      <i class="bi {{ $tab['icon'] }}" aria-hidden="true"></i>
      <span class="guest-tournament-nav__label">{{ $tab['label'] }}</span>
    </a>
  @endforeach
</nav>
@endif
