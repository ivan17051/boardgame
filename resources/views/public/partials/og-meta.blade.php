@php
    $ogTournament = $ogTournament ?? ($tournament ?? null);
    $ogTitle = $ogTitle
        ?? (! empty($ogTournament['nama'])
            ? $ogTournament['nama'].' — Omahjong'
            : 'Omahjong — Turnamen Mahjong');
    $ogDescription = $ogDescription ?? (
        is_array($ogTournament)
            ? trim(implode(' · ', array_filter([
                $ogTournament['jenis_label'] ?? 'Mahjong',
                ! empty($ogTournament['tanggal'])
                    ? \Carbon\Carbon::parse($ogTournament['tanggal'])->locale('id')->translatedFormat('d F Y')
                    : null,
                ($ogTournament['status'] ?? null) === 'open' ? 'Pendaftaran dibuka' : null,
            ])))
            : 'Daftar turnamen mahjong, lihat klasemen, dan pantau juara di Omahjong.'
    );
    $ogImage = $ogImage
        ?? ($ogTournament['share_image_url'] ?? null)
        ?? \App\Support\BornpadelMahjongTournaments::defaultShareImageUrl();
    $ogUrl = $ogUrl ?? url()->current();
    $ogImageAlt = $ogImageAlt
        ?? ($ogTournament['nama'] ?? 'Omahjong');
@endphp

<meta name="description" content="{{ $ogDescription }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Omahjong">
<meta property="og:title" content="{{ $ogTitle }}">
<meta property="og:description" content="{{ $ogDescription }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:image:alt" content="{{ $ogImageAlt }}">
<meta property="og:url" content="{{ $ogUrl }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $ogTitle }}">
<meta name="twitter:description" content="{{ $ogDescription }}">
<meta name="twitter:image" content="{{ $ogImage }}">
