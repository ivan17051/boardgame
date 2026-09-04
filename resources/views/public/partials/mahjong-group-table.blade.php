@php
  $members = $group['members'] ?? [];
  $showInput = ! empty($canInputScores) && count($members) === 4;
  $inputMembers = collect($members)->map(function ($member) {
    return [
      'id' => (int) ($member['id_grup_member'] ?? 0),
      'nama' => $member['nama'] ?? 'Pemain',
    ];
  })->values();
@endphp
<article class="card group-card">
  <div class="group-card-header">
    <div class="d-flex flex-wrap align-items-center gap-2 min-w-0">
      <span class="group-card-title">
        <i class="bi bi-diagram-3 me-1"></i>{{ $group['nama'] ?? 'Grup' }}
      </span>
      @if (! empty($group['babak']))
        <small class="text-secondary">— Babak {{ $group['babak'] }}</small>
      @endif
      <span class="badge text-bg-info">{{ count($members) }} pemain</span>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
      @if ($showInput)
        <button
          type="button"
          class="btn btn-sm btn-primary js-input-poin"
          data-grup-id="{{ $group['id'] ?? '' }}"
          data-grup-name="{{ $group['nama'] ?? 'Grup' }}"
          data-babak="{{ $group['babak'] ?? '' }}"
          data-members='@json($inputMembers)'
          data-no-loading
          title="Input poin untuk semua pemain di grup"
        >
          <i class="bi bi-pencil-square me-1"></i>Input Poin
        </button>
      @endif
    </div>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr>
          <th>Pemain</th>
          <th class="text-center" style="width:4.5rem" title="Jumlah menang (ronde)">W</th>
          <th class="text-center" style="width:6.5rem">Akumulasi</th>
          <th class="text-center">Poin Babak</th>
          <th class="text-center" style="width:5.5rem">Total</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($members as $member)
          @php
            $entries = $member['entries'] ?? [];
          @endphp
          <tr>
            <td class="fw-semibold">{{ $member['nama'] ?? '—' }}</td>
            <td class="text-center">
              <span class="badge text-bg-warning text-dark">{{ (int) ($member['menang'] ?? 0) }}</span>
            </td>
            <td class="text-center text-secondary">{{ (int) ($member['poin_akumulasi'] ?? 0) }}</td>
            <td class="text-center">
              <span class="badge text-bg-info">{{ (int) ($member['poin_didapat'] ?? 0) }}</span>
              @if (! empty($entries))
                <div class="mt-1 d-flex flex-wrap justify-content-center gap-1">
                  @foreach ($entries as $entry)
                    @php
                      $poin = (int) ($entry['poin'] ?? 0);
                    @endphp
                    <span class="badge text-bg-light text-dark border {{ ! empty($entry['is_winner']) ? 'border-warning' : '' }}">
                      @if (! empty($entry['is_winner']))
                        <i class="bi bi-trophy-fill text-warning me-1" title="Pemenang ronde"></i>
                      @endif
                      {{ $poin > 0 ? '+' : '' }}{{ $poin }}
                    </span>
                  @endforeach
                </div>
              @endif
            </td>
            <td class="text-center">
              <span class="badge text-bg-primary">{{ (int) ($member['total_poin'] ?? 0) }}</span>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="text-center text-secondary py-4">Belum ada pemain di grup ini.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</article>
